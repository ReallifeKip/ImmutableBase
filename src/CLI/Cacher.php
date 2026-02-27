<?php

namespace ReallifeKip\ImmutableBase\CLI;

use Composer\Autoload\ClassLoader;
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
        $outputPath     = StaticStatus::$cachePath ??= dirname(dirname((new ReflectionClass(ClassLoader::class))->getFileName()), 2) . '/ib-cache.php';
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
     */
    private function indexDirectory(string $dir)
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
            $classes = self::parseFullClassname($file->getRealPath());
            foreach ($classes as $class) {
                ob_start();
                try {
                    if (
                        match (true) {
                            $class === self::class                         => true,
                            empty(trim($class))                            => true,
                            (new ReflectionClass($class))->isAbstract()    => true,
                            (new ReflectionClass($class))->isTrait()       => true,
                            !is_subclass_of($class, self::$baseClasses[0]) => true,
                            default                                        => false
                        }
                    ) {
                        continue;
                    }
                    if (is_subclass_of($class, self::$baseClasses[3])) {
                        $class::from('');
                        continue;
                    }
                    $class::fromArray([]);
                } catch (Throwable) {
                    // Silently skip classes that cannot be instantiated
                } finally {
                    ob_end_clean();
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
     * @return array FQCN or null if no class declaration is found.
     */
    private static function parseFullClassname(string $path)
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $tokens           = token_get_all($content);
        $namespace        = '';
        $classes          = [];
        $gettingNamespace = false;
        $gettingClass     = false;
        foreach ($tokens as $token) {
            switch (true) {
                case $token === ';':
                    $gettingNamespace = false;
                    continue 2;
                case $token[0] === T_NAMESPACE:
                    $gettingNamespace = true;
                    break;
                case $token[0] === T_CLASS:
                    $gettingClass = true;
                    break;
                default:
                    break;
            }
            if ($gettingNamespace && ($token[0] === T_NAME_QUALIFIED || $token[0] === T_STRING)) {
                $namespace .= $token[1];
            }
            if ($gettingClass && $token[0] === T_STRING) {
                $classes[]    = $namespace ? "$namespace\\{$token[1]}" : $token[1];
                $gettingClass = false;
            }
        }

        return $classes;
    }
}
