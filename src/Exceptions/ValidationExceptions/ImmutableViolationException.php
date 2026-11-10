<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Thrown when code tries to change an object held by an ImmutableProxy:
 * assigning or unsetting one of its properties, or calling a method that
 * modifies it. The held object can only be replaced, through with().
 */
class ImmutableViolationException extends ValidationException
{
}
