<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\InitializationException;

/**
 * Thrown when a non-nullable property receives null or is absent from
 * the input data array.
 *
 * @param string $propertyName The missing or null property.
 */
class RequiredValueException extends InitializationException
{
    public function __construct(string $propertyName)
    {
        parent::__construct("Property '$propertyName' must be present and non-null.");
    }
}
