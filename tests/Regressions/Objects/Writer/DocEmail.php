<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class DocEmail extends SingleValueObject
{
    public string $value;
}
