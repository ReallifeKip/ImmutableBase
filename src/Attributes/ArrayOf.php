<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

use ReallifeKip\ImmutableBase\Enums\Native;

/**
 * Declares that an array property contains typed elements of a specific
 * ImmutableBase subclass, or validated PHP scalar values via Native.
 *
 * Accepts either a class-string of an ImmutableBase subclass, or a
 * Native enum case (Native::string, Native::int, Native::float, Native::bool)
 * for primitive typed arrays.
 *
 * Must be applied to properties typed exactly as `array`. Union types
 * or non-array types will trigger InvalidArrayOfUsageException at scan time.
 *
 * @example #[ArrayOf(OrderItemDTO::class)] public array $items
 * @example #[ArrayOf(Native::string)]      public array $tags
 * @example #[ArrayOf(Native::int)]         public array $scores
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    /**
     * @param class-string|Native $class FQCN of an ImmutableBase subclass, or a Native enum case for primitive typed arrays.
     */
    private function __construct(
        public string $class
    ) {}
}
