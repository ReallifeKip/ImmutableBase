<?php

namespace Tests\Attacks\Objects\ForbiddenTypes;

use Closure;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ClosurePropertyDTO extends DataTransferObject
{
    public Closure $handler;
}
