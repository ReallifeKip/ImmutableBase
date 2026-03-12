<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown at scan time when a property declares a type that ImmutableBase
 * cannot resolve: `object`, `iterable`, standalone `null`, non-ImmutableBase
 * classes, or non-enum classes (e.g. DateTime, Closure, Stringable).
 *
 * @param string $type The forbidden type name.
 */
class InvalidPropertyTypeException extends DefinitionException
{
    public function __construct(string $type)
    {
        parent::__construct("'{$type}' is not a supported property type. Allowed types are ImmutableBase subclasses, Enums, and scalar builtins (string, int, float, bool). For nullable properties, use ?string, null|string, etc.");
    }
}
