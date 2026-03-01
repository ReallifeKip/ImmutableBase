<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

/**
 * Errors arising from invalid input data during object construction
 * (fromArray, fromJson) or mutation (with). Indicates that the provided
 * values do not satisfy the declared type constraints.
 */
abstract class InitializationException extends RuntimeException
{
}
