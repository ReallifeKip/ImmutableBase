<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\ImmutableBase;

/** Extends the engine directly instead of DTO / VO / SVO. */
readonly class DocDirect extends ImmutableBase
{
    public string $direct;
}
