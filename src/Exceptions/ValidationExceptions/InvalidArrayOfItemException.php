<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Thrown when an element within an #[ArrayOf] array cannot be resolved
 * to the declared target type — either an ImmutableBase subclass or a
 * Native scalar type (e.g. an integer where a DTO or string is expected).
 *
 * @param int $index The zero-based index of the invalid element.
 * @param string $targetType The expected ImmutableBase class FQCN or Native scalar type name.
 */
class InvalidArrayOfItemException extends ValidationException
{
    public function __construct(int $index, string $targetType)
    {
        parent::__construct(
            class_exists($targetType) ?
            "Item at index $index is not or cannot be initialized as $targetType." :
            "Item at index $index is not of type $targetType."
        );
    }
}
