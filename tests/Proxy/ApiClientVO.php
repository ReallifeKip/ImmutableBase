<?php

declare (strict_types = 1);

namespace Tests\Proxy;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class ApiClientVO extends ValueObject
{
    public VendorConfigProxy $config;

    public function validate(): bool
    {
        return $this->config->get()->timeout > 0;
    }
}
