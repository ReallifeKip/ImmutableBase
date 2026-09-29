<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI;

use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use ReallifeKip\ImmutableBase\CLI\writer\Markdown;
use ReallifeKip\ImmutableBase\CLI\writer\Mermaid;
use ReallifeKip\ImmutableBase\CLI\writer\Typescript;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Internal\Metadata;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;
use Throwable;

/**
 * CLI tool that generates documentation (Mermaid class diagrams,
 * Markdown property tables, or TypeScript declarations) for all
 * ImmutableBase subclasses in the current working directory.
 *
 * Delegates rendering to Mermaid, Markdown, or Typescript strategy
 * classes. Output format is selected via CLI argument or interactive prompt.
 *
 * Usage: vendor/bin/ib-writer [--format=mmd|md|ts] [--output=directory]
 *        (no --format: interactive prompts)
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
        Markdown::$enums = [];
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
                'ts'    => Typescript::$header,
                default => Markdown::$header,
            },
            match (self::$type) {
                'mmd'   => Mermaid::namespaceBlocksGenerate($namespaceGroups, $classMap, $shortNameCount),
                'ts'    => Typescript::contentGenerate($classMap),
                default => Markdown::namespaceBlocksGenerate($namespaceGroups, $classMap, $shortNameCount)
            },
            self::$type === 'mmd' ? self::buildRelations($classMap, $shortNameCount) : []
        );
        $content = implode("\n", $content);
        file_put_contents(self::$outputDir, $content, LOCK_EX);
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
        foreach (Metadata::all() as $value) {
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
                default                                   => null, // extends ImmutableBase directly
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
                self::collectProperties($entry['ref'], $classMap),
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
     * Extracts the property names and type strings to draw inside a class
     * node. A property inherited from a class that has its own node (a
     * concrete parent in the class map) is skipped, since the inheritance
     * arrow already shows it. A property inherited from a class without a
     * node (an abstract parent, or the framework bases) is drawn here, or it
     * would appear nowhere in the diagram. Strips nullable '?' prefix.
     *
     * @param ReflectionClass $ref
     * @param ClassMap $classMap
     * @return array<string, string> Property name => type string.
     */
    private static function collectProperties(ReflectionClass $ref, array $classMap): array
    {
        foreach ($ref->getProperties() as $prop) {
            $declaring = $prop->getDeclaringClass()->getName();
            if ($declaring !== $ref->getName() && isset($classMap[$declaring])) {
                continue;
            }
            $type                    = $prop->getType();
            $typeStr                 = ltrim((string) $type, '?');
            $props[$prop->getName()] = $typeStr;
        }

        return $props ?? [];
    }

    /**
     * Compiles every ImmutableBase class in the working directory (see
     * ClassDiscovery) so buildClassMap() can read their metadata. Classes
     * with definition errors are skipped, with a notice unless silent.
     *
     * @return void
     */
    private static function indexDirectory(): void
    {
        ClassDiscovery::compileAll(self::$scanDir, null, static function (string $class, Throwable $e): void {
            if (!self::$silent && $e instanceof DefinitionException) {
                fwrite(STDERR, "\033[33m[Skipped] $class: {$e->getMessage()}\033[0m\n");
            }
        });
    }

}
