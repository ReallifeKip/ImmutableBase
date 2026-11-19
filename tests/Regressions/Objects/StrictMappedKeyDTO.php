<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[Strict]
readonly class StrictMappedKeyDTO extends DataTransferObject
{
    #[InputKeyTo(KeyCase::Camel)]
    public int $userId;
}
