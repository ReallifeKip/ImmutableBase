<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Writes a property under exactly this key when serializing with
 * `toArray(true)` / `toJson(true)`.
 *
 * Takes precedence over #[OutputCaseTo] on the same property and over the
 * class-level #[OutputCaseTo]. Passing a KeyCase to toArray() / toJson()
 * still converts every key, including this one. Pair it with #[InputKey]
 * to round-trip a key that no KeyCase can produce.
 *
 * @example #[OutputKey('2fa')] public bool $twoFactor;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class OutputKey
{
    private function __construct(string $key)
    {
        // Prevents manual instantiation of the attribute.
    }
}
