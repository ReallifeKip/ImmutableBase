<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Enums;

/**
 * Represents PHP primitive scalar types for use with #[ArrayOf].
 *
 * Allows typed primitive arrays to be declared without wrapping values
 * in a SingleValueObject. Each case maps directly to a PHP builtin
 * scalar type validated by ImmutableBase at construction time.
 *
 * `object` and `array` are intentionally excluded — ImmutableBase does
 * not manage non-IB objects, and nested arrays have no meaningful
 * typed-collection semantics within this system.
 *
 * @example #[ArrayOf(Native::string)] public array $tags
 * @example #[ArrayOf(Native::int)]    public array $scores
 */
enum Native: string {
    case string = 'string';
    case int    = 'int';
    case float  = 'float';
    case bool   = 'bool';
}
