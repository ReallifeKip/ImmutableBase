<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DefaultValuesBothDTO extends DataTransferObject
{
    #[Defaults(true)]
    public ?bool $bool;
    #[Defaults(1)]
    public ?int $int;
    #[Defaults([9, 9, 9])]
    public ?array $array;

    public static function defaultValues(): array
    {
        return [
            'bool'  => false,
            'int'   => 0,
            'array' => [],
        ];
    }
}
