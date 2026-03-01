<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Excludes null-valued properties from serialization output (toArray/toJson).
 *
 * When applied at class level, affects all nullable properties of the class.
 * When applied at property level, affects only that property.
 * Can be overridden per-property with #[KeepOnNull].
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class SkipOnNull
{
    private function __construct()
    {}
}
