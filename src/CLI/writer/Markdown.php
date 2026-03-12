<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI\writer;

use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\CLI\Writer;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Types;
use ReflectionProperty;

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
    public static array $enums  = [];
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
    public static function namespaceBlocksGenerate(array $namespaceGroups, array $classMap, array $shortNameCount): array
    {
        foreach ($namespaceGroups as $classes) {
            foreach ($classes as $entry) {
                $content = array_merge($content ?? [], Writer::buildClassBlock($entry, $classMap, $shortNameCount));
            }
        }
        $content = array_merge($content ?? [], self::enumBlocksGenerate());

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
    public static function contentBlocksGenerate(array $classMap, array $entry): array
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
        $content[] = '|name|required|type|default|description|';
        $content[] = '|--|--|--|--|--|';
        foreach ($types as $type) {
            /** @var ReflectionProperty $propRef */
            $propRef  = $type['propertyRef'] ?? $ref->getProperty($type['propertyName']);
            $propDocs = self::docParser($propRef->getDocComment());
            $required = $type['allowsNull'] ? '' : 'yes';
            if ($type['isUnion']) {
                /** @var UnionType $type */
                $typeNames = array_map(self::unionTypeNamesParser(...), $type['types']);
                $typename  = join('<br>', $typeNames);
            } else {
                /** @var NamedType $type */
                $typename = $type['typename']['string'];
                if (!$type['isBuiltin']) {
                    $shortname = explode('\\', $typename);
                    $shortname = end($shortname);
                    $typename  = "[$shortname](#$typename)";
                }
                if (enum_exists($type['typename']['string'])) {
                    self::$enums[$type['typename']['string']] = true;
                }
            }
            $desc    = $propDocs['desc'] ?: '-';
            $default = '-';
            if (isset($type['defaults'])) {
                $default = $type['defaults'];
                $default = match (true) {
                    $default instanceof \BackedEnum   => (string) $default->value,
                    $default instanceof \UnitEnum     => $default->name,
                    $default instanceof ImmutableBase => $default::class,
                    is_callable($default)             => '(dynamic)',
                    \is_array($default)               => json_encode($default),
                    $default === null                 => 'null',
                    $default === false                => 'false',
                    default                           => (string) $default
                };
            }
            $content[] = "| {$type['propertyName']} | $required | $typename | $default | $desc |";
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

            if (enum_exists($typename)) {
                self::$enums[$typename] = true;
            }

            return "[$shortname](#$typename)";
        }

        return $typename;
    }
    /**
     * Generates Markdown documentation blocks for all Enum classes
     * referenced by ImmutableBase property types. Only Enums actually
     * used as property types are included. BackedEnum cases display
     * both name and backing value; UnitEnum cases display name only.
     *
     * @return list<string>
     */
    public static function enumBlocksGenerate(): array
    {
        $content = [];

        foreach (self::$enums as $enumClass => $_) {
            $ref       = new \ReflectionEnum($enumClass);
            $shortName = $ref->getShortName();
            $isBacked  = $ref->isBacked();

            $content[] = "# {$shortName} {#{$enumClass}}";
            $content[] = '';

            if ($isBacked) {
                $backingType = $ref->getBackingType()->getName();
                $content[]   = "| case | value ({$backingType}) |";
                $content[]   = '|--|--|';
                foreach ($ref->getCases() as $case) {
                    /** @var \ReflectionEnumBackedCase $case */
                    $value     = $case->getBackingValue();
                    $content[] = "| {$case->getName()} | {$value} |";
                }
            } else {
                $content[] = '| case |';
                $content[] = '|--|';
                foreach ($ref->getCases() as $case) {
                    $content[] = "| {$case->getName()} |";
                }
            }

            $content[] = '';
            $content[] = '---';
            $content[] = '';
        }

        return $content;
    }
}
