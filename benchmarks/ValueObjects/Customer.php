<?php

declare (strict_types = 1);

namespace Benchmarks\ValueObjects;

use Benchmarks\SingleValueObjects\Email;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class Customer extends ValueObject
{
    public string $name;
    public Email $email; // SVO
    public Address $billingAddress; // VO Nested VO
    public ?Address $shippingAddress; // Nullable VO
}
