<?php

declare (strict_types = 1);

namespace Benchmarks\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class Email extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_EMAIL) !== false;
    }
}
