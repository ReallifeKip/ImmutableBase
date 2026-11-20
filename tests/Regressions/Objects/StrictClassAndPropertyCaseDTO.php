<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\InputCaseTo;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[Strict, InputCaseTo(KeyCase::Snake)]
readonly class StrictClassAndPropertyCaseDTO extends DataTransferObject
{
    public string $first_name;

    #[InputCaseTo(KeyCase::Macro)]
    public string $API_KEY;
}
