<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class ProfileVO extends ValueObject
{
    public string $name;
    public int $age;
    public string $email;
}
