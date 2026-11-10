<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class UnionGetDTO extends DataTransferObject
{
    public UnionGetProxy $config;
}
