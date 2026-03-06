<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

readonly class UncacheableDefaultValueVO extends ValueObject
{
    public array $functions;

    public static function defaultValues(): array
    {
        return [
            'functions' => [function () {}],
        ];
    }
}
