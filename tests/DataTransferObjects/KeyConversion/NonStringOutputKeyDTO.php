<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKey;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class NonStringOutputKeyDTO extends DataTransferObject
{
    #[OutputKey(1)]
    public int $a;
}
