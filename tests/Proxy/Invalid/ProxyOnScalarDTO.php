<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Proxy\OpaqueThing;

/** #[Proxy] on a property that is not an ImmutableProxy. */
readonly class ProxyOnScalarDTO extends DataTransferObject
{
    #[Proxy(OpaqueThing::class)]
    public string $name;
}
