<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKey;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class EmptyInputKeyDTO extends DataTransferObject
{
    #[InputKey('')]
    public int $a;
}
