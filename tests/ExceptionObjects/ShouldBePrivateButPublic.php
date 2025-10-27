<?php

declare(strict_types=1);

namespace Tests\ExceptionObjects;

use ReallifeKip\ImmutableBase\ValueObject;
use ReallifeKip\ImmutableBase\ImmutableBase;

#[ValueObject]
class ShouldBePrivateButPublic extends ImmutableBase
{
    public string $string;
}
