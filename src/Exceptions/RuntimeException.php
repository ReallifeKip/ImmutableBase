<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

/**
 * Base for errors that occur during object construction or mutation
 * due to invalid input data (e.g. type mismatches, missing required
 * values). These represent runtime failures, not programming errors.
 */
abstract class RuntimeException extends ImmutableBaseException
{
}
