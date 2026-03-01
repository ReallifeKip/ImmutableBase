<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class GmailSVO extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return str_contains($this->value, 'gmail.com');
    }
}
