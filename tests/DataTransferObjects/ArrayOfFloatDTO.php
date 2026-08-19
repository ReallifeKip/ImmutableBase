<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Enums\Native;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ArrayOfFloatDTO extends DataTransferObject
{
    #[ArrayOf(Native::float, Native::int)]
    public array $floatOrInt;

    #[ArrayOf(Native::float, Native::string)]
    public array $floatOrString;
}
