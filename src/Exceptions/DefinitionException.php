<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

/**
 * Errors arising from invalid class structure or attribute configuration.
 * Thrown during the first instantiation of a class when property metadata
 * is scanned via reflection. Subsequent instantiations reuse cached
 * metadata and will not re-trigger these exceptions.
 */
abstract class DefinitionException extends LogicException
{
}
