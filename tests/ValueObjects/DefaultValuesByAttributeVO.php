<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class DefaultValuesByAttributeVO extends ValueObject
{
    #[Defaults(false)]
    public ?bool $bool;
    #[Defaults(0)]
    public ?int $int;
    #[Defaults([])]
    public ?array $array;

}
