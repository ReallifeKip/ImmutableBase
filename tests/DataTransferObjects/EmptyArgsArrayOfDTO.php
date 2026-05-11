<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class EmptyArgsArrayOfDTO extends DataTransferObject
{
    #[ArrayOf()]
    public array $items;
}
