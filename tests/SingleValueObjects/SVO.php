<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class SVO extends SingleValueObject
{
    public string $value;
}
