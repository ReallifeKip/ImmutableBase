<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Proxy\OpaqueThing;
use Tests\Proxy\VendorConfigProxy;

/** #[Proxy] names a different class than the subclass's get() returns. */
readonly class MismatchedTargetDTO extends DataTransferObject
{
    #[Proxy(OpaqueThing::class)]
    public VendorConfigProxy $config;
}
