<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Still uses the 4.x name, which no longer exists. */
#[InputKeyTo(KeyCase::Camel)]
readonly class RemovedClassAttributeDTO extends DataTransferObject
{
    public ?string $firstName;
}
