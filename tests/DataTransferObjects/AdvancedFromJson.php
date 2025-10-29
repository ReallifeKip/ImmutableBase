<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

class AdvancedFromJson extends BasicFromJson
{
    public readonly BasicFromJson $basic;
    /** @var BasicFromJson[] */
    #[ArrayOf(BasicFromJson::class)]
    public readonly array $arrayOfBasics;
    public readonly string|int|BasicFromJson $union;
    public readonly null|string|int $unionNullable;
}
