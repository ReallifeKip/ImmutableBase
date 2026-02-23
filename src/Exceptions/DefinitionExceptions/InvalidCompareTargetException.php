<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown by equals() when comparison is not possible. Two scenarios:
 *   1. Class mismatch — comparing instances of different ImmutableBase classes.
 *   2. Uncomparable element — a plain array contains a non-ImmutableBase object
 *      for which semantic equality cannot be determined.
 *
 * @param string $classname The expected class or the uncomparable type.
 * @param string|null $actualType The actual class when mismatched, null for uncomparable elements.
 */
class InvalidCompareTargetException extends DefinitionException
{
    public function __construct(string $classname, ?string $actualType = null)
    {
        if ($actualType) {
            $message = "equals() expects an instance of $classname, $actualType given.";
        } else {
            $message = "$classname cannot be compared.";
        }
        parent::__construct($message);
    }
}
