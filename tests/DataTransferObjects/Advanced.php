<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\ArrayOf;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class Advanced extends Basic
{
    public readonly Basic $basic;
    /** @var Basic[] */
    #[ArrayOf(Basic::class)]
    public readonly array $arrayOfBasics;
    public readonly string|int $union;
    public readonly null|string|int $unionNullable;
}
