<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\TestObjects\RegularClass;

readonly class InvalidTypeArrayOfDTO extends DataTransferObject
{
    #[ArrayOf(RegularClass::class)]
    public string $regulars;
}
