<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown at scan time when a property is not declared as public.
 * All ImmutableBase properties must be public readonly.
 *
 * @param string $propertyName The non-public property name.
 */
class InvalidVisibilityException extends DefinitionException
{
    public function __construct(string $propertyName)
    {
        parent::__construct("'$propertyName' must be public and readonly.");
    }
}
