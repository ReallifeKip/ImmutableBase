<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\ImmutableProxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class UnknownTargetDTO extends DataTransferObject
{
    #[Proxy('Tests\Proxy\DoesNotExist')]
    public ImmutableProxy $thing;
}
