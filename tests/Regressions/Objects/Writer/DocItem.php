<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DocItem extends DataTransferObject
{
    public string $sku;
}
