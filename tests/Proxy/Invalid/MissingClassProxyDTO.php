<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class MissingClassProxyDTO extends DataTransferObject
{
    public MissingClassProxy $thing;
}
