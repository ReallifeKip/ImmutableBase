<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\InitializationException;

/**
 * Thrown when a JSON string cannot be decoded. Triggered by fromJson()
 * and by with() when receiving a malformed JSON string input.
 */
class InvalidJsonException extends InitializationException
{
    public function __construct()
    {
        parent::__construct('Invalid Json string.');
    }
}
