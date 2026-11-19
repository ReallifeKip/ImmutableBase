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
     * Applies #[InputKey] and class- and property-level #[InputKeyTo] remapping to an input array.
     *
     * #[InputKey] runs first: an input key equal to a declared key supplies that
     * property's value. Property-level overrides come next: for each property that carries its own
     * InputKeyTo, the first input key whose conversion to the property's case
     * equals the property name supplies that property's value. A key used this
     * way (by either attribute) is consumed (removed from the result) unless it is itself the name of a
     * property, so strict mode does not report it as undeclared.
     * Class-level: every remaining string key is converted to the declared KeyCase.
     *
     * @param array<string|int, mixed> $data  Raw input array.
     * @param array{inputKeyCase: KeyCase|null, propertyInputKeyCases: array<string, KeyCase>|null, propertyInputKeys?: array<string, string>|null, types: array<string, mixed>} $class Compiled class metadata.
     * @return array<string|int, mixed>
     */
    public static function remapInput(array $data, array $class): array
    {
        $mapped   = [];
        $consumed = [];
        // ?? null: caches written before #[InputKey] existed have no such entry
        foreach ($class['propertyInputKeys'] ?? [] as $inputKey => $propName) {
            if (\array_key_exists($inputKey, $data)) {
                $mapped[$propName] = $data[$inputKey];
                if (!isset($class['types'][$inputKey])) {
                    $consumed[$inputKey] = true;
                }
            }
        }
        foreach ($class['propertyInputKeyCases'] ?? [] as $propName => $propKeyCase) {
            foreach ($data as $inputKey => $inputValue) {
                if (\is_string($inputKey) && self::convert($inputKey, $propKeyCase) === $propName) {
                    $mapped[$propName] = $inputValue;
                    if (!isset($class['types'][$inputKey])) {
                        $consumed[$inputKey] = true;
                    }
                    break;
                }
            }
        }
        $rest = $consumed === [] ? $data : array_diff_key($data, $consumed);
        if ($class['inputKeyCase'] !== null) {
            $rest = self::remapKeys($rest, $class['inputKeyCase']);
        }

        return $mapped === [] ? $rest : array_replace($rest, $mapped);
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
