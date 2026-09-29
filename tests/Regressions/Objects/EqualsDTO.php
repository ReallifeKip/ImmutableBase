<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class EqualsDTO extends DataTransferObject
{
    public array $raw;
    #[ArrayOf(Level::class)]
    public array $levels;
}
