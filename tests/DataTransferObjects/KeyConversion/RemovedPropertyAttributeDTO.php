<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Still uses the 4.x name, which no longer exists. */
readonly class RemovedPropertyAttributeDTO extends DataTransferObject
{
    #[OutputKeyTo(KeyCase::Snake)]
    public ?string $firstName;
}
