<?php

declare (strict_types = 1);

namespace Tests\Proxy;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\ImmutableProxy;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ApiClientDTO extends DataTransferObject
{
    public string $name;
    public VendorConfigProxy $config;
    public ?VendorConfigProxy $fallback;
    /** @var ImmutableProxy<OpaqueThing>|null */
    #[Proxy(OpaqueThing::class)]
    public ?ImmutableProxy $opaque;
    /** @var ImmutableProxy<ClosureHolder>|null */
    #[Proxy(ClosureHolder::class)]
    public ?ImmutableProxy $hook;
}
