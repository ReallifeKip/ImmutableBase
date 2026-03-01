<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[SkipOnNull]
readonly class SkipOnNullDTO extends DataTransferObject
{
    public ?string $string;
}
