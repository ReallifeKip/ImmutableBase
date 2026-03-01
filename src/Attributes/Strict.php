<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Enables strict input validation for a class. When applied, any keys
 * present in the input array that do not correspond to declared properties
 * will trigger a StrictViolationException.
 *
 * Can be overridden per-class with #[Lax]. The global ImmutableBase::strict()
 * toggle achieves the same effect without requiring an attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
abstract class Strict
{
}
