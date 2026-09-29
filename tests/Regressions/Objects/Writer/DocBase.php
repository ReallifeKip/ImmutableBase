<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

abstract readonly class DocBase extends DataTransferObject
{
    public int $inheritedId;
}
