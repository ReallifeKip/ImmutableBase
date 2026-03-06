<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class DefaultValuesByFunctionVO extends ValueObject
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
