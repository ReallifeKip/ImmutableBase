<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when an #[ArrayOf] attribute specifies a target class that is
 * not a subclass of ImmutableBase (i.e. not a DTO, VO, or SVO).
 */
class InvalidArrayOfTargetException extends DefinitionException
{
    public function __construct()
    {
        parent::__construct('#[ArrayOf] target must be a subclass of DataTransferObject, ValueObject, or SingleValueObject.');
    }
}
