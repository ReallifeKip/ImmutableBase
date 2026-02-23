<?php

namespace ReallifeKip\ImmutableBase\CLI\writer;

use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Types;
use ReflectionProperty;
use Writer;

/**
 * Rendering strategy for Markdown documentation output. Generates
 * property tables with type links, nullability indicators, and
 * PHPDoc descriptions extracted from source annotations.
 *
 * @phpstan-import-type NamespaceGroup from Types
 * @phpstan-import-type NamespaceGroups from Types
 * @phpstan-import-type ClassMap from Types
 * @phpstan-import-type Class from Types
 * @phpstan-import-type Type from Types
 * @phpstan-import-type BaseType from Types
 * @phpstan-import-type NamedType from Types
 * @phpstan-import-type UnionType from Types
 * @phpstan-import-type NamedTypeFromUnion from Types
 */
abstract class Markdown
{
    public static array $header = [];
    /**
     * Iterates all namespace groups and delegates each class to
     * Writer::buildClassBlock() for Markdown table generation.
     * Returns a flat array of rendered lines.
     *
     * @param NamespaceGroups $namespaceGroups
     * @param ClassMap $classMap
     * @param list<int> $shortNameCount
     * @return list<string>
     */
    public static function namespaceBlocksGenerate(array $namespaceGroups, array $classMap, array $shortNameCount)
    {
        foreach ($namespaceGroups as $classes) {
            foreach ($classes as $entry) {
                $content = array_merge($content ?? [], Writer::buildClassBlock($entry, $classMap, $shortNameCount));
            }
        }

        return $content;
    }
    /**
     * Renders a single class as a Markdown section: heading with anchor,
     * optional description and #[Spec] value, followed by a property table
     * with columns for name, required status, type (with internal links),
     * and PHPDoc description.
     *
     * @param ClassMap $classMap
     * @param NamespaceGroup $entry
     * @return list<string>
     */
    public static function contentBlocksGenerate(array $classMap, array $entry)
    {
        $fullClass = $entry['fullClass'];
        $ref       = $entry['ref'];
        $types     = $classMap[$fullClass]['types'] ?? [];
        $docs      = self::docParser($ref->getDocComment());
        $specAttr  = $ref->getAttributes(Spec::class)[0] ?? null;
        $spec      = $specAttr ? ($specAttr->getArguments()[0] ?? null) : null;

        $content[] = "# {$entry['shortName']} {#{$fullClass}}";
        if ($docs['desc']) {
            $content[] = "> {$docs['desc']}";
        }
        if ($spec) {
            $content[] = $spec;
        }
        $content[] = '';
        $content[] = '|name|required|type|description|';
        $content[] = '|--|--|--|--|';

        foreach ($types as $type) {
            /** @var ReflectionProperty $propRef */
            $propRef  = $type['propertyRef'] ?? $ref->getProperty($type['propertyName']);
            $propDocs = self::docParser($propRef->getDocComment());
            $required = $type['allowsNull'] ? '' : 'yes';

            if ($type['isUnion']) {
                /** @var UnionType $type */
                $typeNames = array_map(self::unionTypeNamesParser(...), $type['types']);
                $typename  = implode('<br>', $typeNames);
            } else {
                /** @var NamedType $type */
                $typename = $type['typename']['string'];
                if (!$type['isBuiltin']) {
                    $shortname = explode('\\', $typename);
                    $shortname = end($shortname);
                    $typename  = "[$shortname](#$typename)";
                }
            }

            $content[] = "| {$type['propertyName']} | $required | $typename | " . ($propDocs['desc'] ?: '-') . ' |';
        }

        $content[] = '';
        $content[] = '---';
        $content[] = '';

        return $content ?? [];
    }

    /**
     * Extracts the @desc tag value from a PHPDoc comment block.
     * Returns an empty description for missing or malformed comments.
     *
     * @param string|false $comment Raw PHPDoc comment or false.
     * @return array{desc: string}
     */
    private static function docParser(string | false $comment): array
    {
        if (!\is_string($comment) || mb_trim($comment) === '') {
            $comment = '/** */';
        }

        return [
            'desc' => (string) ((Writer::$docblock->create($comment))->getTagsByName('desc')[0] ?? null),
        ];
    }
    /**
     * Formats a single union member type for Markdown output. Non-builtin
     * types are rendered as internal anchor links; builtin types are
     * rendered as plain text.
     *
     * Declared as final protected to satisfy static analyzers when
     * referenced via first-class callable syntax ($this->method(...)).
     *
     * @param NamedTypeFromUnion $type Single union member metadata.
     * @return string Formatted type string.
     * @note Final protected visibility used because some analyzers fail to resolve references via First-class callable syntax (...).
     */
    final protected static function unionTypeNamesParser(array $type): string
    {
        $typename = $type['typename']['string'];
        if (!$type['isBuiltin']) {
            $shortname = explode('\\', $typename);
            $shortname = end($shortname);

            return "[$shortname](#$typename)";
        }

        return $typename;
    }
}
