<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\InitializationException;

/**
 * Thrown when a value's type does not match the declared property type.
 * Includes the expected type and the actual runtime type in the message.
 *
 * @param string $expect The expected type name (e.g. "string", "int", class name).
 * @param mixed $actualValue The value that failed type checking.
 */
class InvalidValueException extends InitializationException
{
    public function __construct(string $expect, mixed $actualValue)
    {
        parent::__construct("Invalid value: expected $expect, got " . get_debug_type($actualValue) . '.');
    }

}
