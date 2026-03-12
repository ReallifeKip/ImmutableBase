<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Declares a per-property fallback value used when the input payload
 * does not contain that property key.
 *
 * Resolution precedence is:
 *   1. Explicit input value
 *   2. ImmutableBase::defaultValues()
 *   3. #[Defaults(...)]
 *
 * This attribute is ignored when the key is explicitly present with `null`.
 *
 * Note: the attribute name is `#[Defaults]` (plural). `#[Default]` is not
 * supported and will not be recognized.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Defaults
{
    /**
     * @param mixed $value Fallback value to apply when the property key is absent.
     */
    private function __construct(
        public mixed $value
    ) {}
}
