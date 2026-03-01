<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Thrown when an element within an #[ArrayOf] array cannot be resolved
 * to the declared target class (e.g. an integer where a DTO is expected).
 *
 * @param int $index The zero-based index of the invalid element.
 * @param string $propertyName The property containing the array.
 * @param string $targetClassname The expected ImmutableBase class for each element.
 */
class InvalidArrayOfItemException extends ValidationException
{
    public function __construct(int $index, string $propertyName, string $targetClassname)
    {
        parent::__construct("Item at index $index is not or cannot be initialized as $targetClassname.");
    }
}
