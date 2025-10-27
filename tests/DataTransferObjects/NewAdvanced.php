<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

class NewAdvanced extends NewBasic
{
    public readonly NewBasic $basic;
    /** @var NewBasic[] */
    #[ArrayOf(NewBasic::class)]
    public readonly array $arrayOfBasics;
    public readonly string|int|NewBasic $union;
    public readonly null|string|int $unionNullable;
}
