<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class HotmailSVO extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return str_contains($this->value, 'hotmail.com');
    }
}
