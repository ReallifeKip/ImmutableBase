<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputCaseTo;
use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Combines InputCaseTo(Camel) and OutputCaseTo(Snake):
 * accepts snake_case input keys and serializes back to snake_case,
 * enabling a transparent round-trip for snake_case data sources.
 */
#[InputCaseTo('Camel')]
readonly class InvalidKeyCaseDTO extends DataTransferObject
{
    public string $nickName;
}
