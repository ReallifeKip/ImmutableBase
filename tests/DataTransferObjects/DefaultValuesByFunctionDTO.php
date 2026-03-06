<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DefaultValuesByFunctionDTO extends DataTransferObject
{
    public ?bool $bool;
    public ?int $int;
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
