<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Thrown when input data contains keys not declared as properties on
 * a class operating in strict mode (via #[Strict] or global strict toggle).
 *
 * @param string $classname The class that rejected the input.
 * @param string[] $array The list of redundant key names.
 */
class StrictViolationException extends ValidationException
{
    public function __construct(string $classname, array $array)
    {
        $redundantKeys = join(', ', $array);

        parent::__construct("Disallowed '$redundantKeys' for $classname.");
    }
}
