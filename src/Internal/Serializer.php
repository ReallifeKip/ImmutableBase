<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use BackedEnum;
use JsonSerializable;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use UnitEnum;

/**
 * Serializes an object to its array form for toArray() / toJson().
 *
 * @internal Not part of the public API; may change without notice.
 */
final class Serializer
{
    /**
     * Serializes $object to an associative array. Respects #[SkipOnNull]
     * (omits null-valued properties) and #[KeepOnNull] (overrides SkipOnNull
     * to retain null). Array properties are serialized element by element.
     *
     * @param KeyCase|bool $keyCase false: property names as-is; true: each
     *     property's #[OutputKeyTo] case; a KeyCase: that case for every key,
     *     nested objects included.
     * @throws InvalidSerializeTargetException
     * @return array<string, mixed>
     */
    public static function toArray(ImmutableBase $object, KeyCase | bool $keyCase): array
    {
        $types = Metadata::get($object::class)['types'];
        foreach (get_object_vars($object) as $name => $value) {
            $type = $types[$name];
            if ($type['skipOnNull'] && $value === null && !$type['keepOnNull']) {
                continue;
            }
            $outputName = match (true) {
                $keyCase instanceof KeyCase => KeyCaser::convert($name, $keyCase),
                $keyCase                    => $type['outputKey'],
                default                     => $name,
            };
            $result[$outputName] = \is_array($value)
            ? array_map(static fn($v) => self::toArrayOrValue($v, $keyCase), $value)
            : self::toArrayOrValue($value, $keyCase);
        }

        return $result ?? [];
    }

    /**
     * Converts a value to its array-serializable form for toArray()/toJson().
     * Dispatch order matters: SVO and BackedEnum both have ->value, but SVO
     * must be checked first. UnitEnum serializes to ->name since it has no
     * backed value. An ImmutableBase instance delegates to its own toArray().
     *
     * Scan-time validation keeps foreign objects out of typed properties, but
     * `mixed` and plain `array` properties can still hold one: a
     * JsonSerializable is serialized through jsonSerialize(), anything else
     * throws InvalidSerializeTargetException.
     *
     * @param mixed $value The property value to convert: scalar passthrough, SVO→value, enum→value/name, IB→toArray().
     * @throws InvalidSerializeTargetException
     * @return mixed The array-serializable representation.
     */
    private static function toArrayOrValue(mixed $value, KeyCase | bool $keyCase = false): mixed
    {
        return match (true) {
            !\is_object($value)                 => $value,
            $value instanceof SingleValueObject => $value->value,
            $value instanceof BackedEnum        => $value->value,
            $value instanceof UnitEnum          => $value->name,
            $value instanceof ImmutableBase     => $value->toArray($keyCase),
            $value instanceof JsonSerializable  => $value->jsonSerialize(),
            default                             => throw new InvalidSerializeTargetException(get_debug_type($value)),
        };
    }
}
