<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Child DTO with its own OutputKeyTo(Macro), independent of the parent's Snake. */
#[OutputKeyTo(KeyCase::Macro)]
readonly class NestedOutputChildDTO extends DataTransferObject
{
    public string $childField;
}
