<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ProtectedPropertyDTO extends DataTransferObject
{
    protected string $string;
}
