<?php

declare (strict_types = 1);

namespace Tests\Proxy;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\ImmutableProxy;

#[Proxy(VendorConfig::class)]
final readonly class VendorConfigProxy extends ImmutableProxy
{
}
