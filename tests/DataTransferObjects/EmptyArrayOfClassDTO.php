<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class EmptyArrayOfClassDTO extends DataTransferObject
{
    #[ArrayOf('')]
    public array $regulars;
}
