<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

#[Spec()]
readonly class InvalidSpec extends ValueObject
{
    public string $string;
    public function validate(): bool
    {
        return false;
    }
}
