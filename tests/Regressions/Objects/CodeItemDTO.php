<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class CodeItemDTO extends DataTransferObject
{
    public string $code;
}
