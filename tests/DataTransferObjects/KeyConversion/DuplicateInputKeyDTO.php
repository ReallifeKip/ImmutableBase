<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKey;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DuplicateInputKeyDTO extends DataTransferObject
{
    #[InputKey('id')]
    public int $a;

    #[InputKey('id')]
    public int $b;
}
