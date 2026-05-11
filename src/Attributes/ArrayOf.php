<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

use ReallifeKip\ImmutableBase\Enums\Native;

/**
 * Declares that an array property contains typed elements of one or more
 * ImmutableBase subclasses, or validated PHP scalar values via Native.
 *
 * Accepts one or more class-strings of ImmutableBase subclasses, or Native
 * enum cases (Native::string, Native::int, Native::float, Native::bool)
 * for primitive typed arrays. When multiple types are given, each element
 * is resolved against the declared types in order (first match wins).
 *
 * Must be applied to properties typed exactly as `array`. Union types
 * or non-array types will trigger InvalidArrayOfUsageException at scan time.
 *
 * @example #[ArrayOf(OrderItemDTO::class)]              public array $items
 * @example #[ArrayOf(Native::string)]                   public array $tags
 * @example #[ArrayOf(Native::int)]                      public array $scores
 * @example #[ArrayOf(ShippingDTO::class, PickupDTO::class)] public array $deliveries
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    /** @var list<string> */
    public readonly array $classes;

    /**
     * @param class-string|Native ...$classes One or more FQCNs of ImmutableBase subclasses, or Native enum cases.
     */
    private function __construct(string | Native ...$classes)
    {
        $this->classes = array_values($classes);
    }
}
