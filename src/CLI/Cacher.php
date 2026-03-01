<?php

namespace ReallifeKip\ImmutableBase\CLI;

use Composer\Autoload\ClassLoader;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\StaticStatus;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

/**
 * CLI tool that pre-generates serialized property metadata for all
 * ImmutableBase subclasses found in a directory tree. The output file
 * can be loaded via ImmutableBase::loadCache() to bypass runtime
 * reflection scanning.
 *
 * Usage: php cacher [directory]
 */
class Cacher
{
    public static bool $silent      = false;
    protected array $classToFileMap = [];
    /** @var array<int, class-string> */
    private static array $baseClasses = [
        ImmutableBase::class,
        DataTransferObject::class,
        ValueObject::class,
        SingleValueObject::class,
    ];

    /**
     * Scans the target directory, triggers property metadata compilation
     * for all discovered ImmutableBase classes, then serializes the cache to disk.
     *
     * Strips non-serializable entries (ReflectionClass, ReflectionMethod,
     * Closure) from the metadata before export.
     *
     * @param string $dir Root directory to scan for ImmutableBase subclasses.
     */
    public function scan(string $dir): void
    {
        $outputPath     = StaticStatus::$cachePath ??= dirname(dirname((new ReflectionClass(ClassLoader::class))->getFileName()), 2) . '/ib-cache.php'; // @codeCoverageIgnore
        $exclude        = array_flip(['ref', 'validateMethod', 'hydrator']);
        $excludeType    = array_flip(['ref', 'typeRef', 'resolver', 'propertyRef']);
        $excludeSubType = array_flip(['typeRef']);
        $cache          = [];
        $this->indexDirectory($dir);
        foreach (StaticStatus::$properties as $className => $props) {
            $entry = array_diff_key($props, $exclude);
            foreach ($entry['types'] as $name => $type) {
                $clean = array_diff_key($type, $excludeType);
                if (!empty($clean['types'])) {
                    foreach ($clean['types'] as $i => $subType) {
                        $clean['types'][$i] = array_diff_key($subType, $excludeSubType);
                    }
                }
                $entry['types'][$name] = $clean;
            }
            $cache[$className] = $entry;
        }
        file_put_contents($outputPath, "<?php\n\nreturn " . var_export($cache, true) . ";\n", LOCK_EX);
    }

    /**
     * Recursively walks the directory tree, discovers PHP files containing
     * ImmutableBase subclasses, and force-instantiates each class to populate
     * StaticStatus::$properties with compiled metadata.
     *
     * Instantiation errors are silently caught — classes with unsatisfiable
     * required properties will still have their metadata partially compiled
     * via buildPropertyInheritanceChain() before the exception occurs.
     *
     * @param string $dir Root directory to scan.
     * @return void
     */
    private function indexDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (
                match (true) {
                    $file->isDir()                  => true,
                    $file->getExtension() !== 'php' => true,
                    default                         => str_contains($file->getRealPath(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
                }
            ) {
                continue;
            }
            $content = file_get_contents($file->getRealPath());
            $classes = $content ? self::parseFullClassname($content) : [];
            foreach ($classes as $class) {
                try {
                    if (
                        match (true) {
                            empty(trim($class))                            => true,
                            !class_exists($class)                          => true,
                            (new ReflectionClass($class))->isAbstract()    => true,
                            (new ReflectionClass($class))->isTrait()       => true,
                            !is_subclass_of($class, self::$baseClasses[0]) => true,
                            default                                        => false
                        }
                    ) {
                        continue;
                    }
                    $ref = new ReflectionClass($class);
                    ($method = $ref->getMethod('buildPropertyInheritanceChain'))->setAccessible(true); // NOSONAR
                    $method->invoke(null, $ref->newInstanceWithoutConstructor()); // NOSONAR
                } catch (DefinitionException | Throwable $e) {
                    match (true) {
                        $e instanceof DefinitionException && !self::$silent => fwrite(STDERR, "\033[33m[Skipped] $class: {$e->getMessage()}\033[0m\n"),
                        default => null// Silently skip classes that cannot be instantiated
                    };
                }
            }
        }
    }

    /**
     * Extracts the fully-qualified class name from a PHP file by tokenizing
     * its source. Resolves namespace and class name from T_NAMESPACE and
     * T_CLASS tokens respectively.
     *
     * @param string $path Absolute file path.
     * @return list<class-string>
     */
    private static function parseFullClassname(string $content): array
    {
        $tokens           = token_get_all($content);
        $namespace        = '';
        $classes          = [];
        $gettingNamespace = false;
        $gettingClass     = false;
        $prevTokenType    = null;
        foreach ($tokens as $token) {
            if (!\is_array($token)) {
                if ($token === ';') {
                    $gettingNamespace = false;
                }
                continue;
            }
            [$type, $value] = $token;
            match (true) {
                $type === T_NAMESPACE                                  => $gettingNamespace                              = true,
                $type === T_CLASS && $prevTokenType !== T_DOUBLE_COLON => $gettingClass = true,
                default                                                => null
            };
            if ($gettingNamespace && ($type === T_NAME_QUALIFIED || $type === T_STRING)) {
                $namespace .= $value;
            }
            if ($gettingClass && $type === T_STRING) {
                $classes[]    = ltrim("$namespace\\$value", '\\');
                $gettingClass = false;
            }
            match (true) {
                $type !== T_WHITESPACE => $prevTokenType = $type,
                default                => null
            };
        }

        return $classes;
    }
}
