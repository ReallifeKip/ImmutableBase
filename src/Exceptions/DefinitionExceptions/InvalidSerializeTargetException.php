<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown by toArray() / toJson() when a property holds a value that has no
 * serializable form: a non-ImmutableBase object that does not implement
 * JsonSerializable. Such values are only reachable through `mixed` or plain
 * `array` properties.
 *
 * @param string $type The debug type of the unserializable value.
 */
class InvalidSerializeTargetException extends DefinitionException
{
    public function __construct(string $type)
    {
        parent::__construct("$type cannot be serialized; hold an ImmutableBase object, an enum or a JsonSerializable instead.");
    }
}
