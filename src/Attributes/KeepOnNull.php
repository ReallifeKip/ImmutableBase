<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Overrides #[SkipOnNull] for a specific property, ensuring it is retained
 * in serialization output (toArray/toJson) even when its value is null.
 *
 * Can be applied at class level or property level. At class level, acts as
 * a semantic counterpart to #[SkipOnNull] for readability, but has no
 * behavioral effect since #[KeepOnNull] is the default serialization behavior.
 *
 * Only meaningful on properties within a class annotated with #[SkipOnNull].
 * Has no effect on its own.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class KeepOnNull
{
    private function __construct()
    {}
}
