<?php

declare (strict_types = 1);

namespace Tests\Proxy\Invalid;

use ReallifeKip\ImmutableBase\Attributes\Proxy;
use ReallifeKip\ImmutableBase\ImmutableProxy;

#[Proxy('Tests\Proxy\DoesNotExist')]
final readonly class MissingClassProxy extends ImmutableProxy
{
}
