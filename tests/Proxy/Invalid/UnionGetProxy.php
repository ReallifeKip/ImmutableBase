<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\ImmutableProxy;
use Tests\Proxy\OpaqueThing;
use Tests\Proxy\VendorConfig;

/** get() must declare a single class; PHP itself already rejects a nullable one. */
final readonly class UnionGetProxy extends ImmutableProxy
{
    public function get(): VendorConfig | OpaqueThing
    {
        return parent::get();
    }
}
