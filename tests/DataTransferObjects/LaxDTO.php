<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\Lax;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[Lax]
readonly class LaxDTO extends DataTransferObject
{
    public ?string $string;
}
