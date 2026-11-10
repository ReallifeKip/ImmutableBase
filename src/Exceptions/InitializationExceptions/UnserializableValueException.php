<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\InitializationException;

/**
 * Thrown when an object given to an ImmutableProxy cannot be serialized
 * (it holds a Closure, a resource, an anonymous class instance, ...), so it
 * cannot be held as an immutable snapshot.
 */
class UnserializableValueException extends InitializationException
{
    public function __construct(object $value, string $reason)
    {
        parent::__construct(get_debug_type($value) . " cannot be held by an ImmutableProxy: $reason");
    }
}
