<?php

declare (strict_types = 1);

namespace Benchmarks\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class Price extends SingleValueObject
{
    public int $value;
    public function validate(): bool
    {
        return $this->value >= 0;
    }
}
