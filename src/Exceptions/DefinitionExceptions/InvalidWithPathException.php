<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when a deep path in with() targets a property that is neither
 * an array nor an ImmutableBase instance (e.g. "scalarProp.subkey").
 *
 * @param string $path The root property name that cannot be traversed.
 */
class InvalidWithPathException extends DefinitionException
{
    public function __construct(string $path)
    {
        parent::__construct("Cannot deeply update \$$path as it is not an array or a subclass of ImmutableBase.");
    }
}
