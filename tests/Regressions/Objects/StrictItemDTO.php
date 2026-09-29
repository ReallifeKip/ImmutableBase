<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[Strict]
readonly class StrictItemDTO extends DataTransferObject
{
    public string $sku;
}
