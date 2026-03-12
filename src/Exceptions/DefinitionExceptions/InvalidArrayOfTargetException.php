<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when an #[ArrayOf] attribute specifies a target that is neither
 * a subclass of ImmutableBase (DTO, VO, or SVO) nor a Native enum case
 * for primitive typed arrays.
 */
class InvalidArrayOfTargetException extends DefinitionException
{
    public function __construct()
    {
        parent::__construct('#[ArrayOf] target must be a subclass of DataTransferObject, ValueObject, or SingleValueObject.');
    }
}
