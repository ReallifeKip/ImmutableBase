<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\BasicTrait;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Internal\Metadata;
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
    /** @var array<class-string, array<string, mixed>> Cacheable default per class and property. */
    private array $defaults = [];

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
        $outputPath     = Metadata::cachePath();
        $exclude        = array_flip(['ref', 'validateMethod', 'hydrator', 'defaultsMap', 'prepareChain']);
        $excludeType    = array_flip(['ref', 'typeRef', 'resolver', 'propertyRef']);
        $excludeSubType = array_flip(['typeRef']);
        $cache          = [];
        $this->indexDirectory($dir);
        foreach (Metadata::all() as $classname => $props) {
            $entry = array_diff_key($props, $exclude);
            foreach ($entry['types'] as $name => $type) {
                if (isset($this->defaults[$classname]) && \array_key_exists($name, $this->defaults[$classname])) {
                    $type['defaults'] = $this->defaults[$classname][$name];
                }
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
     * Compiles every ImmutableBase class under $dir (see ClassDiscovery) and
     * records each one's cacheable default values for the export. Classes
     * with definition errors are skipped, with a notice unless silent.
     *
     * @param string $dir Root directory to scan.
     * @return void
     */
    private function indexDirectory(string $dir): void
    {
        ClassDiscovery::compileAll(
            $dir,
            function (string $class, ImmutableBase $prototype): void {
                $this->defaults[$class] = self::cacheableDefaults($class, $prototype::defaultValues(), (new ReflectionClass($class))->getProperties());
            },
            static function (string $class, Throwable $e): void {
                if (!self::$silent && $e instanceof DefinitionException) {
                    fwrite(STDERR, "\033[33m[Skipped] $class: {$e->getMessage()}\033[0m\n");
                }
            }
        );
    }

    /**
     * Validates if a default value is serializable for caching.
     *
     * This method ensures that only scalar values, arrays, or nulls are stored
     * in the pre-generated cache. If an object (such as a nested ValueObject
     * or a DateTime instance) is detected as a default value, it is flagged
     * as non-cacheable to prevent serialization errors.
     *
     * Non-cacheable defaults will trigger a terminal warning and are cached as
     * null, forcing the engine to resolve these values at runtime.
     *
     * @param class-string $classname The fully-qualified name of the class being scanned.
     * @param array<property-string, mixed> $defaults
     * @param ReflectionProperty[] $properties The name of the property being validated.
     * @return array<string, mixed> The default to cache for each property.
     */
    private static function cacheableDefaults(string $classname, array $defaults, array $properties): array
    {
        foreach ($properties as $property) {
            $name    = $property->name;
            $default = $defaults[$name] ?? self::getAttributeArgument($property, Defaults::class);
            if (self::containsNonSerializable($default)) {
                $type = get_debug_type($default);
                fwrite(STDERR, "\033[31m[Notice] $classname: '$property' not cacheable ($type). Will resolve at runtime only.\033[0m\n");
                $default = null;
            }
            $cacheable[$name] = $default;
        }

        return $cacheable ?? [];
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
        if ($value instanceof \UnitEnum) {
            return false;
        }
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
