<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DefaultValuesByAttributeDTO extends DataTransferObject
{
    #[Defaults(false)]
    public ?bool $bool;
    #[Defaults(0)]
    public ?int $int;
    #[Defaults([])]
    public ?array $array;

}
