<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class MultiArrayOfDTO extends DataTransferObject
{
    #[ArrayOf(DTO1::class, DTO2::class)]
    public array $items;
}
