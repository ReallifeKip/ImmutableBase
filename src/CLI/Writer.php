<?php

namespace ReallifeKip\ImmutableBase\CLI;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use ReallifeKip\ImmutableBase\CLI\writer\Markdown;
use ReallifeKip\ImmutableBase\CLI\writer\Mermaid;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\StaticStatus;
use ReallifeKip\ImmutableBase\Types;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;

/**
 * CLI tool that generates documentation (Mermaid class diagrams or
 * Markdown property tables) for all ImmutableBase subclasses in the
 * current working directory.
 *
 * Delegates rendering to Mermaid or Markdown strategy classes. Output
 * format is selected via CLI argument or interactive prompt.
 *
 * Usage: php writer [mmd|md] [output-directory]
 *
 * @phpstan-import-type ClassMap from Types
 * @phpstan-import-type NamespaceGroup from Types
 * @phpstan-import-type NamespaceGroups from Types
 * @phpstan-import-type NamedType from Types
 * @phpstan-import-type UnionType from Types
 * @phpstan-import-type Props from Types
 */
class Writer
{
    public static string $type;
    public static string $scanDir;
    public static string $outputDir;
    /** @var array<class-string, string> */
    private static array $stereotypes = [];
    public static bool $silent        = false;
    public static DocBlockFactoryInterface $docblock;
    /** @var array<int, class-string> */
    private static array $baseClasses = [
        ImmutableBase::class,
        DataTransferObject::class,
        ValueObject::class,
        SingleValueObject::class,
    ];

    /**
     * Entry point. Scans the working directory for ImmutableBase classes, builds
     * the class map and namespace groupings, delegates rendering to the
     * selected format strategy, and writes the output file.
     *
     * @param string $type
     * @param string $outputDir
     * @return void
     */
    public static function generate(string $type, string $outputDir): void
    {
        self::$type      = $type;
        self::$scanDir   = getcwd();
        self::$outputDir = $outputDir;
        self::$docblock  = DocBlockFactory::createInstance();
        file_put_contents($outputDir, '', LOCK_EX);
        self::indexDirectory();
        $classMap = self::buildClassMap();
        foreach ($classMap as $info) {
            $counts[$info['shortName']] = ($counts[$info['shortName']] ?? 0) + 1;
        }
        $shortNameCount  = $counts ?? [];
        $namespaceGroups = self::buildNamespaceGroups($classMap);
        $content         = array_merge(
            match (self::$type) {
                'mmd'   => Mermaid::$header,
                default => Markdown::$header,
            },
            match (self::$type) {
                'mmd'   => Mermaid::namespaceBlocksGenerate($namespaceGroups, $classMap, $shortNameCount),
                default => Markdown::namespaceBlocksGenerate($namespaceGroups, $classMap, $shortNameCount)
            },
            self::$type === 'mmd' ? self::buildRelations($classMap, $shortNameCount) : []
        );
        file_put_contents(self::$outputDir, implode("\n", $content), LOCK_EX);
    }

    /**
     * Constructs a lookup table of all non-abstract ImmutableBase classes from
     * StaticStatus::$properties. Each entry includes reflection data,
     * short name, namespace, property types, and stereotype (DTO/VO/SVO).
     *
     * @return ClassMap
     */
    private static function buildClassMap(): array
    {
        foreach (StaticStatus::$properties as $value) {
            $fullClass = $value['name'];
            $ref       = new ReflectionClass($fullClass);
            if ($ref->isAbstract()) {
                continue;
            }
            $classMap[$fullClass] = [
                'ref'       => $ref,
                'shortName' => $ref->getShortName(),
                'namespace' => $ref->getNamespaceName() ?: 'Global',
                'types'     => $value['types'],
            ];
            self::$stereotypes[$fullClass] = match (true) {
                $ref->isSubclassOf(self::$baseClasses[1]) => 'DTO',
                $ref->isSubclassOf(self::$baseClasses[3]) => 'SVO',
                $ref->isSubclassOf(self::$baseClasses[2]) => 'VO',
            };
        }

        return $classMap ?? [];
    }

    /**
     * Groups classes by namespace for structured output rendering.
     * Each group contains entries with the FQCN, short name, and
     * ReflectionClass reference.
     *
     * @param ClassMap $classMap
     * @return NamespaceGroups
     */
    private static function buildNamespaceGroups(array $classMap): array
    {
        foreach ($classMap as $fullClass => $info) {
            $groups[$info['namespace']][] = [
                'fullClass' => $fullClass,
                'shortName' => $info['shortName'],
                'ref'       => $info['ref'],
            ];
        }

        return $groups ?? [];
    }

    /**
     * Dispatches a single class entry to the active format strategy
     * for content block generation.
     *
     * @param NamespaceGroup $entry
     * @param ClassMap $classMap
     * @param list<int> $shortNameCount
     * @return list<string>
     */
    public static function buildClassBlock(array $entry, array $classMap, array $shortNameCount): array
    {
        $fullClass = $entry['fullClass'];

        return match (self::$type) {
            'mmd'   => Mermaid::contentBlocksGenerate(
                self::collectProperties($entry['ref']),
                self::displayNameGenerator($fullClass, $classMap, $shortNameCount),
                self::$stereotypes[$fullClass] ?? null
            ),
            default => Markdown::contentBlocksGenerate($classMap, $entry)
        };
    }

    /**
     * @param ClassMap $classMap
     * @param list<int> $shortNameCount
     * @return list<string>
     */
    private static function buildRelations(array $classMap, array $shortNameCount): array
    {
        $relations = [];
        foreach ($classMap as $fullClass => $info) {
            $name = self::displayNameGenerator($fullClass, $classMap, $shortNameCount);
            if ($relation = self::addInheritanceRelation($info['ref'], $name, $classMap, $shortNameCount)) {
                $relations[] = $relation;
            }
            if ($relation = Mermaid::addCompositionRelations($info['types'], $name, $classMap, $shortNameCount)) {
                $relations = array_merge($relation, $relations);
            }
        }

        return ['', implode("\n", array_unique($relations))];
    }

    /**
     * Produces a Mermaid inheritance arrow if the class has a concrete,
     * non-abstract parent that exists in the class map. Skips base
     * framework classes (ImmutableBase, DTO, VO, SVO).
     *
     * @param ReflectionClass $ref
     * @param class-string $name Display name of the child class.
     * @param ClassMap $classMap
     * @param array<string, int> $shortNameCount $shortNameCount
     * @return string|null Mermaid arrow line, or null if no eligible parent.
     */
    private static function addInheritanceRelation(ReflectionClass $ref, string $name, array $classMap, array $shortNameCount): string | null
    {
        $parent          = $ref->getParentClass();
        $parentClassName = $parent->getName();
        if (\in_array($parentClassName, self::$baseClasses, true) || $parent->isAbstract() || !isset($classMap[$parentClassName])) {
            return null;
        }
        $parentName = self::displayNameGenerator($parentClassName, $classMap, $shortNameCount);

        return "    {$parentName} <|-- {$name} : ";
    }

    /**
     * Resolves the display name for a class in diagram output. Uses the
     * short name when unique; prepends the namespace (with backslashes
     * replaced by underscores) when multiple classes share the same short name.
     *
     * @param class-string $fullClass
     * @param ClassMap $classMap
     * @param list<int> $shortNameCount
     * @return string Display name safe for Mermaid/Markdown identifiers.
     */
    public static function displayNameGenerator(string $fullClass, array $classMap, array $shortNameCount): string
    {
        if (!isset($classMap[$fullClass])) {
            $class = explode('\\', $fullClass);

            return end($class);
        }
        $info = $classMap[$fullClass];
        if (isset($shortNameCount[$info['shortName']]) && $shortNameCount[$info['shortName']] > 1) {
            return str_replace('\\', '_', $info['namespace']) . "_{$info['shortName']}";
        }

        return $info['shortName'];
    }

    /**
     * Extracts locally-declared property names and their type strings
     * from a ReflectionClass. Skips inherited properties to avoid
     * duplication in diagram output. Strips nullable '?' prefix.
     *
     * @param ReflectionClass $ref
     * @return array<string, string> Property name => type string.
     */
    private static function collectProperties(ReflectionClass $ref): array
    {
        foreach ($ref->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $type                    = $prop->getType();
            $typeStr                 = ltrim((string) $type, '?');
            $props[$prop->getName()] = $typeStr;
        }

        return $props ?? [];
    }

    /**
     * Recursively discovers and force-instantiates all eligible ImmutableBase
     * subclasses in the working directory to populate StaticStatus
     * with compiled property metadata. Excludes vendor/ directory.
     * @return void
     */
    private static function indexDirectory(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::$scanDir));
        foreach ($iterator as $file) {
            if (!self::isEligibleFile($file)) {
                continue;
            }
            $class = self::parseFullClassName($file->getRealPath());
            if (!self::isEligibleClass($class)) {
                continue;
            }
            self::tryInstantiateClass($class);
        }
    }

    /**
     * Determines if a file should be considered for class discovery.
     * Accepts only .php files outside the vendor/ directory.
     *
     * @param SplFileInfo $file
     * @return bool
     */
    private static function isEligibleFile(SplFileInfo $file): bool
    {
        return match (true) {
            $file->isDir()                  => false,
            $file->getExtension() !== 'php' => false,
            default                         => !str_contains($file->getRealPath(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
        };
    }

    /**
     * Determines if a discovered class name is a concrete, non-abstract
     * ImmutableBase subclass eligible for documentation generation.
     *
     * @param class-string|null $class
     * @return bool
     */
    private static function isEligibleClass(?string $class): bool
    {
        return match (true) {
            $class === null || trim($class) === '' || !class_exists($class)       => false,
            $class === self::class || (new ReflectionClass($class))->isAbstract() => false,
            default                                                               => is_subclass_of($class, self::$baseClasses[0])
        };
    }

    /**
     * Force-instantiates a class to trigger property metadata compilation
     * via buildPropertyInheritanceChain(). SVOs receive a type-appropriate
     * dummy value; DTOs and VOs receive an empty array. Exceptions are
     * silently caught — metadata compilation occurs before validation.
     *
     * @param class-string $class
     * @return void
     */
    private static function tryInstantiateClass(string $class): void
    {
        try {
            $ref = new ReflectionClass($class);
            ($method = $ref->getMethod('buildPropertyInheritanceChain'))->setAccessible(true); // NOSONAR
            $method->invoke(null, $ref->newInstanceWithoutConstructor()); // NOSONAR
        } catch (DefinitionException | Throwable $e) {
            if ($e instanceof DefinitionException && !self::$silent) {
                fwrite(STDERR, "\033[33m[Skipped] $class: {$e->getMessage()}\033[0m\n");
            }
            // Silently skip classes that cannot be instantiated
        }
    }

    /**
     * Extracts the fully-qualified class name from a PHP source file
     * by tokenizing its contents. Handles both simple and qualified
     * namespace declarations.
     *
     * @param string $path Absolute file path.
     * @return class-string|null FQCN or null if no class declaration found.
     */
    private static function parseFullClassName(string $path): ?string
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }
        $tokens           = token_get_all($content);
        $namespace        = [];
        $class            = '';
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
                $type === T_CLASS && $prevTokenType !== T_DOUBLE_COLON                  => $gettingClass                 = true,
                $type === T_NAMESPACE                                                   => $gettingNamespace                                              = true,
                $gettingNamespace && ($type === T_NAME_QUALIFIED || $type === T_STRING) => $namespace[] = $value,
                default                                                                 => null
            };
            if ($gettingClass && $type === T_STRING) {
                $class = $value;
                break;
            }
            match (true) {
                $type !== T_WHITESPACE => $prevTokenType = $type,
                default                => null
            };
        }
        if (!$class) {
            return null;
        }

        return ltrim(implode('', $namespace) . "\\$class", '\\');
    }
}
