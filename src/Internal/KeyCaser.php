<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use ReallifeKip\ImmutableBase\Enums\KeyCase;

/**
 * Key naming conventions: converting a name to a KeyCase, and remapping an
 * input array's keys per the class- and property-level #[InputKeyTo].
 * Output keys use convert() directly (see Serializer).
 *
 * @internal Not part of the public API; may change without notice.
 */
final class KeyCaser
{
    /**
     * Applies class-level and property-level InputKeyTo remapping to an input array.
     *
     * Class-level: converts every string key to the declared KeyCase.
     * Property-level overrides: for each property that carries its own InputKeyTo,
     * scans the original (pre-class-remap) input and converts each key to the
     * property's declared case; on the first match, writes the value under the
     * property name directly.
     *
     * @param array<string|int, mixed> $data  Raw input array.
     * @param array{inputKeyCase: KeyCase|null, propertyInputKeyCases: array<string, KeyCase>|null} $class Compiled class metadata.
     * @return array<string|int, mixed>
     */
    public static function remapInput(array $data, array $class): array
    {
        $original = $data;
        if ($class['inputKeyCase'] !== null) {
            $data = self::remapKeys($original, $class['inputKeyCase']);
        }
        if ($class['propertyInputKeyCases'] !== null) {
            foreach ($class['propertyInputKeyCases'] as $propName => $propKeyCase) {
                foreach ($original as $inputKey => $inputValue) {
                    if (\is_string($inputKey) && self::convert($inputKey, $propKeyCase) === $propName) {
                        $data[$propName] = $inputValue;
                        break;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Converts all string keys of an input array to the specified naming convention.
     * Integer keys are passed through unchanged.
     *
     * @param array<string|int, mixed> $data The raw input array.
     * @param KeyCase $keyCase The target naming convention to apply to each key.
     * @return array<string|int, mixed>
     */
    private static function remapKeys(array $data, KeyCase $keyCase): array
    {
        foreach ($data as $k => $v) {
            $remapped[\is_string($k) ? self::convert($k, $keyCase) : $k] = $v;
        }

        return $remapped ?? [];
    }

    /**
     * Converts a property name to the specified naming convention.
     * Splits the name on camelCase/PascalCase boundaries, underscores,
     * hyphens, and whitespace, then rejoins in the target case.
     *
     * @param string $name The property name to convert.
     * @param KeyCase $keyCase The target naming convention.
     * @return string
     */
    public static function convert(string $name, KeyCase $keyCase): string
    {
        static $separator = [
            'PascalSnake' => '_',
            'Pascal'      => '',
            'Train'       => '-',
            'Snake'       => '_',
            'Kebab'       => '-',
        ];
        $words = array_map(
            'strtolower',
            preg_split('/(?<=[a-z\d])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])|[-_\s]+/', $name, -1, PREG_SPLIT_NO_EMPTY)
        );

        return match ($keyCase) {
            KeyCase::Snake, KeyCase::Kebab => join($separator[$keyCase->value], $words),
            KeyCase::Macro      => join('_', array_map('strtoupper', $words)),
            KeyCase::Camel      => $words[0] . join('', array_map('ucfirst', \array_slice($words, 1))),
            KeyCase::CamelKebab => "{$words[0]}-" . join('-', array_map('ucfirst', \array_slice($words, 1))),
            default => join($separator[$keyCase->value], array_map('ucfirst', $words))
        };
    }
}
