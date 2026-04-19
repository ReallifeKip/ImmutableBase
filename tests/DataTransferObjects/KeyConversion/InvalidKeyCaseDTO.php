<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Combines InputKeyTo(Camel) and OutputKeyTo(Snake):
 * accepts snake_case input keys and serializes back to snake_case,
 * enabling a transparent round-trip for snake_case data sources.
 */
#[InputKeyTo('Camel')]
readonly class InvalidKeyCaseDTO extends DataTransferObject
{
    public string $nickName;
}
