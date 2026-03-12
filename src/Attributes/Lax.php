<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Exempts a class from strict mode enforcement. When applied, redundant
 * keys in input data are silently ignored regardless of the global
 * strict setting or a class-level #[Strict] attribute on parent classes.
 *
 * Takes precedence over both ImmutableBase::strict(true) and #[Strict].
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Lax
{
    private function __construct()
    {
        // Prevents manual instantiation of the attribute.
    }
}
