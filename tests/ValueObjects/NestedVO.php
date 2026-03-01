<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

#[ValidateFromSelf]
readonly class NestedVO extends ValueObject
{
    public Nested2VO $nested2;
}
