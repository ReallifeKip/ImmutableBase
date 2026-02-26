<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[Strict]
readonly class StrictDTO extends DataTransferObject
{
    public ?string $string;
}
