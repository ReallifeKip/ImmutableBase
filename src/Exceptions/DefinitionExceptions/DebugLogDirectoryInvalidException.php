<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown when the debug log directory specified via ImmutableBase::debug()
 * does not exist, is not writable, or is not a directory.
 *
 * @param string $path The invalid directory path.
 */
class DebugLogDirectoryInvalidException extends DefinitionException
{
    public function __construct(string $path)
    {
        parent::__construct("'$path' for debug log does not exist is not writable, or is not a directory.");
    }
}
