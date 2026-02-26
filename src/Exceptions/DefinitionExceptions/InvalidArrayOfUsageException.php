<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when an #[ArrayOf] attribute is applied to a property whose
 * type is not exactly `array` (e.g. a union type or a non-array type).
 *
 * @param string $propertyName The property with the invalid attribute.
 * @param string $type The actual declared type of the property.
 */
class InvalidArrayOfUsageException extends DefinitionException
{
    public function __construct(string $propertyName, string $type)
    {
        parent::__construct("#[ArrayOf] attribute can only be applied to array properties. \$$propertyName is typed as $type");
    }
}
