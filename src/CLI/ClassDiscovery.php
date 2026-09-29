<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI;

use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Internal\Scanner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;

/**
 * Finds and compiles the ImmutableBase classes declared under a directory.
 * Shared by ib-cacher (to export metadata) and ib-writer (to document it).
 *
 * @internal Not part of the public API; may change without notice.
 */
final class ClassDiscovery
{
    /**
     * Compiles every concrete ImmutableBase class declared in the PHP files
     * under $dir, excluding vendor/. Each class is compiled from a prototype
     * whose constructor never runs, so no input is needed.
     *
     * A class that cannot be loaded or compiled (typically a definition
     * error) is skipped and reported to $onSkip; the scan continues.
     *
     * @param callable(class-string, ImmutableBase): void|null $onCompiled Receives each compiled class and its prototype.
     * @param callable(string, Throwable): void|null $onSkip Receives each skipped class and the reason.
     */
    public static function compileAll(string $dir, ?callable $onCompiled = null, ?callable $onSkip = null): void
    {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if (!self::isSourceFile($file)) {
                continue;
            }
            foreach (self::classNamesIn($file->getRealPath()) as $class) {
                try {
                    if (!self::isConcreteImmutable($class)) {
                        continue;
                    }
                    /** @var ImmutableBase $prototype */
                    $prototype = (new ReflectionClass($class))->newInstanceWithoutConstructor();
                    Scanner::compile($prototype);
                    if ($onCompiled !== null) {
                        $onCompiled($class, $prototype);
                    }
                } catch (Throwable $e) {
                    if ($onSkip !== null) {
                        $onSkip($class, $e);
                    }
                }
            }
        }
    }

    /**
     * Extracts the fully-qualified names of every class declared in a PHP
     * source file by tokenizing its contents. Handles both simple and
     * qualified namespace declarations. `Foo::class` and anonymous classes
     * (`new class`) are not declarations and are skipped.
     *
     * @param string $path Absolute file path.
     * @return list<class-string> FQCNs in declaration order; empty if none or unreadable.
     */
    public static function classNamesIn(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $namespace        = '';
        $classes          = [];
        $gettingNamespace = false;
        $gettingClass     = false;
        $prevTokenType    = null;
        foreach (token_get_all($content) as $token) {
            if (!\is_array($token)) {
                if ($token === ';' || $token === '{') {
                    $gettingNamespace = false;
                }
                continue;
            }
            [$type, $value] = $token;
            match (true) {
                $type === T_CLASS && $prevTokenType !== T_DOUBLE_COLON && $prevTokenType !== T_NEW => $gettingClass     = true,
                $type === T_NAMESPACE                                                              => $gettingNamespace = true,
                $gettingNamespace && ($type === T_NAME_QUALIFIED || $type === T_STRING)            => $namespace .= $value,
                default                                                                            => null
            };
            if ($gettingClass && $type === T_STRING) {
                $classes[]    = ltrim("$namespace\\$value", '\\');
                $gettingClass = false;
            }
            if ($type !== T_WHITESPACE) {
                $prevTokenType = $type;
            }
        }

        return $classes;
    }

    /** A .php file outside any vendor/ directory. */
    private static function isSourceFile(SplFileInfo $file): bool
    {
        return !$file->isDir()
        && $file->getExtension() === 'php'
        && !str_contains($file->getRealPath(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);
    }

    /** A loadable, non-abstract subclass of ImmutableBase. */
    private static function isConcreteImmutable(string $class): bool
    {
        return trim($class) !== ''
        && class_exists($class)
        && is_subclass_of($class, ImmutableBase::class)
        && !(new ReflectionClass($class))->isAbstract();
    }
}
