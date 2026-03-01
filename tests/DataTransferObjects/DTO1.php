<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DTO1 extends DataTransferObject
{
    public string $string1;
}
