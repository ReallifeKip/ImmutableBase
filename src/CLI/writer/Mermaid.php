<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI\writer;

use ReallifeKip\ImmutableBase\CLI\Writer;
use ReallifeKip\ImmutableBase\Types;

/**
 * Rendering strategy for Mermaid classDiagram output. Generates
 * class blocks with stereotypes (DTO/VO/SVO), property declarations,
 * and namespace groupings compatible with Mermaid's classDiagram syntax.
 *
 * @phpstan-import-type NamespaceGroups from Types
 * @phpstan-import-type ClassMap from Types
 * @phpstan-import-type NamedType from Types
 * @phpstan-import-type UnionType from Types
 * @phpstan-import-type Type from Types
 */
abstract class Mermaid
{
    public static array $header = [
        '---',
        'config:',
        '    class:',
        '        hideEmptyMembersBox: true',
        '---',
        'classDiagram',
    ];
    /**
     * Generates Mermaid namespace blocks by wrapping each group's class
     * blocks in a `namespace X.Y.Z { ... }` container. Backslashes in
     * PHP namespaces are converted to dots for Mermaid compatibility.
     *
     * @param NamespaceGroups $namespaceGroups
     * @param ClassMap $classMap
     * @param list<int> $shortNameCount
     * @return list<string>
     */
    public static function namespaceBlocksGenerate(array $namespaceGroups, array $classMap, array $shortNameCount): array
    {
        foreach ($namespaceGroups as $namespace => $classes) {
            /** Note: Using str_ireplace because str_replace cannot reach 100% branch coverage. */
            $namespace = str_ireplace('\\', '.', $namespace);
            $content[] = '';
            $content[] = "    namespace {$namespace} {";
            foreach ($classes as $entry) {
                $content = array_merge($content, Writer::buildClassBlock($entry, $classMap, $shortNameCount));
            }
            $content[] = '    }';
        }

        return $content ?? [];
    }
    /**
     * Renders a single class as a Mermaid class block with optional
     * stereotype annotation and typed property declarations.
     * Produces an empty class body when no properties or stereotype exist.
     *
     * @param array<string, string> $props Property name => type string.
     * @param class-string $name Display name for the class node.
     * @param string $stereotype DTO, VO, or SVO label (empty string if none).
     * @return list<string>
     */
    public static function contentBlocksGenerate(array $props, string $name, string $stereotype): array
    {
        $content = ["        class {$name} {"];
        if ($stereotype) {
            $content[] = "            <<{$stereotype}>>";
        }
        foreach ($props as $propName => $propType) {
            $content[] = "            +{$propType} {$propName}";
        }
        $content[] = '        }';

        return $content;
    }

    /**
     * Produces Mermaid composition arrows for all non-builtin, non-union
     * property types that reference another class in the class map.
     * Includes cardinality notation ("0..1" for nullable, "1" otherwise).
     *
     * @param array<string, Type> $types Property type metadata.
     * @param class-string $name Display name of the owning class.
     * @param ClassMap $classMap
     * @param array<string, int> $shortNameCount
     * @return list<string> Mermaid arrow lines.
     */
    public static function addCompositionRelations(array $types, string $name, array $classMap, array $shortNameCount): array
    {
        foreach ($types as $type) {
            if ($type['isBuiltin'] || $type['isUnion']) {
                continue;
            }
            $targetClass = $type['typename']['string'];
            if (!isset($classMap[$targetClass])) {
                continue;
            }
            $targetName = Writer::displayNameGenerator($targetClass, $classMap, $shortNameCount);
            if ($targetName === $name) {
                continue;
            }
            $cardinality = $type['allowsNull'] ? '"0..1"' : '"1"';
            $relations[] = "    {$name} --> {$cardinality} {$targetName} : " . ($type['typename']['short'] ?? '');
        }

        return $relations ?? [];
    }
}
