<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when an ImmutableProxy property or subclass is declared incorrectly:
 * the held class is unknown, missing or contradictory, #[Proxy] is used on a
 * property that is not an ImmutableProxy, or the proxy appears where it is
 * not supported (unions, #[ArrayOf]).
 */
class InvalidProxyException extends DefinitionException
{
}
