<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Proxy\VendorConfigProxy;

/** Unions with a proxy member are not supported yet. */
readonly class ProxyInUnionDTO extends DataTransferObject
{
    public VendorConfigProxy|string $config;
}
