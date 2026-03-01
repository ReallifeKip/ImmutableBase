<?php
namespace Tests\Attacks\Objects;

use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[SkipOnNull]
readonly class KeepOnNullDTO extends DataTransferObject
{
    public ?string $skipped;
    #[KeepOnNull]
    public ?string $kept;
    public ?string $alsoSkipped;
}
