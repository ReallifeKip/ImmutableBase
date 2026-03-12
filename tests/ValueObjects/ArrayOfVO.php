<?php

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Enums\Native;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class ArrayOfVO extends ValueObject
{
    #[ArrayOf(Native::string)]
    public array $strings;
    #[ArrayOf(Native::int)]
    public array $ints;
    #[ArrayOf(Native::float)]
    public array $floats;
    #[ArrayOf(Native::bool)]
    public array $bools;
}
