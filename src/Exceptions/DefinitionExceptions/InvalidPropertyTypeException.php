<?php

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown at scan time when a property declares a type that ImmutableBase
 * cannot resolve: `object`, `iterable`, `null`, non-ImmutableBase classes, or
 * non-enum classes (e.g. DateTime, Closure, Stringable).
 *
 * @param string $type The forbidden type name.
 */
class InvalidPropertyTypeException extends DefinitionException
{
    public function __construct(string $type)
    {
        parent::__construct("'{$type}' is not a subclass of ImmutableBase, an Enum, or a builtin type other than object");
    }
}
