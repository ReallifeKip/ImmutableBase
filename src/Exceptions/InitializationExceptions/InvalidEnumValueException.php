<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\InitializationException;

/**
 * Thrown when a value cannot be resolved to any case of the target enum,
 * neither by constant name lookup nor by BackedEnum::tryFrom().
 *
 * @param string $enumName The fully-qualified enum class name.
 * @param string $value The unresolvable value.
 */
class InvalidEnumValueException extends InitializationException
{
    public function __construct(string $enumName, string $value)
    {
        parent::__construct("'$value' does not match any of $enumName defined names or cases.");
    }
}
