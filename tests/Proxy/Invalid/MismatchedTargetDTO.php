<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Proxy\OpaqueThing;
use Tests\Proxy\VendorConfigProxy;

/** The property's #[Proxy] contradicts the one on its typed subclass. */
readonly class MismatchedTargetDTO extends DataTransferObject
{
    #[Proxy(OpaqueThing::class)]
    public VendorConfigProxy $config;
}
