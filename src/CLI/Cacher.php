<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI;

use Composer\Autoload\ClassLoader;
use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\BasicTrait;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\StaticStatus;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionProperty;
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
    use BasicTrait;
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
        foreach (StaticStatus::$properties as $classname => $props) {
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
            $cache[$classname] = $entry;
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
                            empty(trim($class))                                => true,
                            !class_exists($class)                              => true,
                            ($ref = new ReflectionClass($class))->isAbstract() => true,
                            $ref->isTrait()                                    => true,
                            !is_subclass_of($class, self::$baseClasses[0])     => true,
                            default                                            => false
                        }
                    ) {
                        continue;
                    }
                    ($method = $ref->getMethod('buildPropertyInheritanceChain'))->setAccessible(true); // NOSONAR
                    /** @var ImmutableBase $obj */
                    $obj = $ref->newInstanceWithoutConstructor(); // NOSONAR
                    $method->invoke(null, $obj);
                    self::defaultValueValidate($class, $obj::defaultValues(), $ref->getProperties());
                } catch (DefinitionException | Throwable $e) {
                    match (true) {
                        !self::$silent && $e instanceof DefinitionException => fwrite(STDERR, "\033[33m[Skipped] $class: {$e->getMessage()}\033[0m\n"),
                        default => null
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
     * @param string $content Full PHP file content.
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
    /**
     * Validates if a default value is serializable for caching.
     *
     * This method ensures that only scalar values, arrays, or nulls are stored
     * in the pre-generated cache. If an object (such as a nested ValueObject
     * or a DateTime instance) is detected as a default value, it is flagged
     * as non-cacheable to prevent serialization errors.
     *
     * Non-cacheable defaults will trigger a terminal warning and return null,
     * forcing the engine to resolve these values at runtime.
     *
     * @param class-string $classname The fully-qualified name of the class being scanned.
     * @param array<property-string, mixed> $defaults
     * @param ReflectionProperty[] $properties The name of the property being validated.
     * @return void
     */
    private static function defaultValueValidate(string $classname, array $defaults, array $properties): void
    {
        foreach ($properties as $property) {
            $name    = $property->name;
            $default = $defaults[$name] ?? self::getAttributeArgument($property, Defaults::class);
            if (self::containsNonSerializable($default)) {
                $type = get_debug_type($default);
                fwrite(STDERR, "\033[31m[Notice] $classname: '$property' not cacheable ($type). Will resolve at runtime only.\033[0m\n");
                $default = null;
            }
            StaticStatus::$properties[$classname]['types'][$name]['defaults'] = $default;
        }
    }
    /**
     * Recursively checks whether a value contains any non-serializable
     * elements (objects, Closures, resources) that would cause var_export()
     * to fail or produce invalid cache output.
     *
     * Scalar values and flat arrays pass immediately. Nested arrays are
     * walked recursively; the first non-serializable element short-circuits
     * with true. Circular references are not a concern here — readonly
     * classes cannot produce self-referencing default value structures.
     *
     * @param mixed $value The default value to inspect.
     * @return bool True if the value contains any non-serializable element.
     */
    private static function containsNonSerializable(mixed $value): bool
    {
        if (\is_object($value)) {
            return true;
        }
        if (\is_array($value)) {
            foreach ($value as $v) {
                if (self::containsNonSerializable($v)) {
                    return true;
                }
            }
        }

        return false;
    }
}
