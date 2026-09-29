<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[SkipOnNull]
readonly class DocSparse extends DataTransferObject
{
    public ?string $omittedWhenNull;
    #[KeepOnNull]
    public ?string $keptAsNull;
    public DocItem|DocEmail|null $unionWithNull;
}
