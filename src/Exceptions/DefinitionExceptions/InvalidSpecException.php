<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when a ValueObject or SingleValueObject class has an #[Spec]
 * attribute with no argument or an empty argument.
 *
 * @param string $classname The class with the invalid #[Spec] declaration.
 */
class InvalidSpecException extends DefinitionException
{
    public function __construct(string $classname)
    {
        parent::__construct("#[Spec] value for $classname is required, must be a string, and cannot be empty.");
    }
}
