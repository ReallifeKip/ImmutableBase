<?php

declare (strict_types = 1);

namespace Benchmarks\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class Sku extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return ctype_alnum($this->value);
    }
}
