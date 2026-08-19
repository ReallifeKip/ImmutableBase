<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Package half of the native-parity matrix. Mirrors NativeBuiltinTypes
 * property for property; see NativeParityTest.
 */
readonly class BuiltinTypesDTO extends DataTransferObject
{
    public string $string;
    public int $int;
    public float $float;
    public bool $bool;
    public array $array;
    public int | float $intOrFloat;
    public float | string $floatOrString;
    public ?float $nullableFloat;
}
