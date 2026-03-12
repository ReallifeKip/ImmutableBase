<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\CLI\writer;

use BackedEnum;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;

/**
 * Rendering strategy for TypeScript declaration output. Generates
 * interfaces for DTO/VO classes, type aliases for SVOs, and
 * enum/union type declarations for PHP enums.
 *
 * @phpstan-import-type ClassMap from Types
 * @phpstan-import-type Type from Types
 */
abstract class Typescript
{
    public static array $header = [];
    public static array $enums  = [];

    /**
     * Maps a PHP type to its TypeScript equivalent.
     *
     * Handles primitive types, 'mixed', and 'array' (with optional element type).
     * Non-builtin types (classes, enums) are converted from backslash (\)
     * to dot (.) notation for TS namespace compatibility.
     *
     * @param string $phpType The PHP type name (e.g., 'int', 'array', or a FQCN).
     * @param class-string|null $arrayOf The target class/type if the $phpType is 'array'.
     * @return string The mapped TypeScript type representation.
     */
    private static function phpTypeToTs(string $phpType, ?string $arrayOf = null): string
    {
        $map = static fn(string $type) => match ($type) {
            'string' => 'string',
            'int', 'float' => 'number',
            'bool'   => 'boolean',
            'mixed'  => 'any',
            default  => str_replace('\\', '.', $type),
        };
        if ($phpType === 'array') {
            return ($arrayOf ? $map($arrayOf) : 'unknown') . '[]';
        }

        return $map($phpType);
    }

    /**
     * Resolves a full property type (named or union) to its TypeScript
     * representation. Union members are deduplicated after mapping
     * (e.g. int|float both become `number`).
     *
     * @param Type $type Compiled property type metadata.
     * @return string The TypeScript type string.
     */
    private static function resolvePropertyType(array $type): string
    {
        if ($type['isUnion']) {
            $members = [];
            foreach ($type['types'] as $subType) {
                $mapped           = self::phpTypeToTs($subType['typename']['string']);
                $members[$mapped] = true;
            }

            return implode(' | ', array_keys($members));
        }

        return self::phpTypeToTs($type['typename']['string'], $type['arrayOf'] ?? null);
    }

    /**
     * Collects enum types referenced by a property for later output.
     * BackedEnums store case name => value pairs; UnitEnums store case names only.
     *
     * @param Type $type Property type metadata to inspect for enum references.
     * @return void
     */
    private static function collectEnums(array $type): void
    {
        foreach ($type['typename']['array'] as $t) {
            if (!enum_exists($t)) {
                continue;
            }
            $ref = new ReflectionClass($t);
            if (is_subclass_of($t, BackedEnum::class)) {
                self::$enums[$ref->getNamespaceName()][$ref->getShortName()] = [
                    'cases'    => array_column($t::cases(), 'value', 'name'),
                    'isBacked' => true,
                ];
            } else {
                self::$enums[$ref->getNamespaceName()][$ref->getShortName()] = [
                    'names'    => array_map(static fn($c) => $c->name, $t::cases()),
                    'isBacked' => false,
                ];
            }
        }
    }

    /**
     * Renders a single enum block (backed or unit) as TypeScript lines.
     *
     * @param string $name The enum short name.
     * @param array $enum The enum metadata (cases/names + isBacked flag).
     * @return list<string>
     */
    private static function renderEnum(string $name, array $enum): array
    {
        $lines = [];
        if ($enum['isBacked']) {
            $lines[] = "    enum $name {";
            foreach ($enum['cases'] as $k => $v) {
                $formatted = \is_int($v) ? $v : "'$v'";
                $lines[]   = "        $k = $formatted,";
            }
            $lines[] = '    }';
        } else {
            $names   = implode(' | ', array_map(static fn($n) => "'$n'", $enum['names']));
            $lines[] = "    type $name = $names";
        }

        return $lines;
    }

    /**
     * Processes and renders a collection of enums for a specific namespace.
     *
     * This method categorizes enums into unit enums (rendered as union type aliases)
     * and backed enums (rendered as TypeScript enums). It ensures a consistent
     * output order by rendering unit enums first, followed by backed enums.
     *
     * @param array<string, array> $enums A map of enum short names to their metadata.
     * @return list<string> The rendered TypeScript code lines for the enum block.
     */
    private static function renderEnumBlock(array $enums): array
    {
        $lines  = [];
        $backed = [];
        $unit   = [];

        foreach ($enums as $name => $enum) {
            if ($enum['isBacked']) {
                $backed[$name] = $enum;
            } else {
                $unit[$name] = $enum;
            }
        }

        foreach ($unit as $name => $enum) {
            $lines = array_merge($lines, self::renderEnum($name, $enum));
        }
        foreach ($backed as $name => $enum) {
            $lines = array_merge($lines, self::renderEnum($name, $enum));
        }

        return $lines;
    }

    /**
     * Generates TypeScript declarations for all ImmutableBase classes.
     *
     * SVOs are emitted as type aliases (scalar), DTO/VOs as interfaces,
     * and referenced enums as TS enums or union literal types.
     * Within each namespace block, output order is: type aliases,
     * interfaces, backed enums, then unit enum type aliases.
     *
     * @param ClassMap $classMap
     * @return list<string>
     */
    public static function contentGenerate(array $classMap): array
    {
        self::$enums = [];
        $namespaces  = [];

        foreach ($classMap as $class) {
            $types     = $class['types'];
            $namespace = $class['namespace'];
            $shortName = $class['shortName'];

            if ($class['ref']->isSubclassOf(SingleValueObject::class)) {
                $type                                        = $types['value'];
                $tsType                                      = self::resolvePropertyType($type);
                $namespaces[$namespace]['types'][$shortName] = $tsType;
                continue;
            }

            $properties = [];
            foreach ($types as $type) {
                $key              = $type['propertyName'] . ($type['allowsNull'] ? '?' : '');
                $properties[$key] = self::resolvePropertyType($type);
                self::collectEnums($type);
            }
            $namespaces[$namespace]['interfaces'][$shortName] = $properties;
        }

        $ts            = [];
        $orphanedEnums = self::$enums;

        foreach ($namespaces as $namespace => $content) {
            $declareNamespace = str_replace('\\', '.', $namespace);
            $localEnums       = $orphanedEnums[$namespace] ?? [];
            unset($orphanedEnums[$namespace]);

            $ts[] = "declare namespace $declareNamespace {";

            foreach ($content['interfaces'] ?? [] as $name => $properties) {
                $ts[] = "    interface $name {";
                foreach ($properties as $k => $v) {
                    $ts[] = "        $k: $v";
                }
                $ts[] = '    }';
            }

            foreach ($content['types'] ?? [] as $name => $tsType) {
                $ts[] = "    type $name = $tsType";
            }

            if ($localEnums) {
                $ts = array_merge($ts, self::renderEnumBlock($localEnums));
            }

            $ts[] = '}';
        }

        foreach ($orphanedEnums as $namespace => $enums) {
            $declareNamespace = str_replace('\\', '.', $namespace);
            $ts[]             = "declare namespace $declareNamespace {";
            $ts               = array_merge($ts, self::renderEnumBlock($enums));
            $ts[]             = '}';
        }

        return $ts;
    }
}
