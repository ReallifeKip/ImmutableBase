<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ArrayOfOnUnionDTO extends DataTransferObject
{
    #[ArrayOf(DTO1::class)]
    public DTO1|string $items;
}
