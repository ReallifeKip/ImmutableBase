<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class SVO1 extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        echo "SVO_1\n";

        return true;
    }
}
