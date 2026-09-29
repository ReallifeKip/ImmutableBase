<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PathItemDTO extends DataTransferObject
{
    public string $sku;
}
