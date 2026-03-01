<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

/**
 * Errors arising from domain validation failures. Covers both
 * user-defined validate() failures (ValidationChainException) and
 * structural violations (StrictViolationException, InvalidArrayOfItemException).
 */
abstract class ValidationException extends RuntimeException
{
}
