<?php

declare (strict_types = 1);

namespace Benchmarks\ValueObjects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class Address extends ValueObject
{
    public string $street;
    public string $city;
    public string $country;
    public ?string $zipCode;
}
