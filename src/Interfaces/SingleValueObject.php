<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Interfaces;

/**
 * Contract for single-value wrapper objects. Guarantees a publicly
 * readable `$value` property of scalar type via PHP 8.4 property hooks.
 *
 * Implemented by the concrete SingleValueObject base class. Used by
 * ImmutableBase internals to distinguish SVOs from compound objects
 * during resolution, serialization, and comparison.
 */
interface SingleValueObject
{
    public string|int|float|bool $value {
        get;
    }
}
