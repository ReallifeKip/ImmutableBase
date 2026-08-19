<?php

declare (strict_types = 1);

namespace Tests\TestObjects;

/**
 * Native half of the parity matrix: a plain class whose typed properties are
 * assigned directly, so PHP's own strict_types rules decide accept/reject and
 * any widening. Mirrors BuiltinTypesDTO property for property.
 *
 * Not an ImmutableBase subclass on purpose — it is the control, not a fixture.
 */
class NativeBuiltinTypes
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
