<?php

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Declares that an array property contains typed elements of a specific
 * ImmutableBase subclass. The target class is passed as the first
 * constructor argument of the attribute.
 *
 * Must be applied to properties typed exactly as `array`. Union types
 * or non-array types will trigger InvalidArrayOfUsageException at scan time.
 *
 * @example #[ArrayOf(OrderItemDTO::class)] public array $items
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
}
