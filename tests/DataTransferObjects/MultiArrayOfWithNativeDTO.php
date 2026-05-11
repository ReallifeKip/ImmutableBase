<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Enums\Native;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class MultiArrayOfWithNativeDTO extends DataTransferObject
{
    #[ArrayOf(DTO1::class, Native::int)]
    public array $items;
}
