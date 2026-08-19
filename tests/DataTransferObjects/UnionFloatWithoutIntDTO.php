<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class UnionFloatWithoutIntDTO extends DataTransferObject
{
    public float|string $widened;
    public bool|string $notWidened;
}
