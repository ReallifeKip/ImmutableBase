<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

/**
 * Base for errors caused by incorrect class definitions that can be
 * detected at scan time (e.g. forbidden property types, invalid attribute
 * usage). These represent programming errors, not invalid input data.
 */
abstract class LogicException extends ImmutableBaseException
{
}
