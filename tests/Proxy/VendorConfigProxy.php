<?php

declare (strict_types = 1);

namespace Tests\Proxy;

use ReallifeKip\ImmutableBase\ImmutableProxy;

final readonly class VendorConfigProxy extends ImmutableProxy
{
    public function get(): VendorConfig
    {
        return parent::get();
    }
}
