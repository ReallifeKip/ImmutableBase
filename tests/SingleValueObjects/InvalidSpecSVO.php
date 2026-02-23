<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

#[Spec()]
readonly class InvalidSpecSVO extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return false;
    }
}
