<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class UncacheableDefaultValueDTO extends ValueObject
{
    public array $functions;

    public static function defaultValues(): array
    {
        return [
            'functions' => [function () {}],
        ];
    }
}
