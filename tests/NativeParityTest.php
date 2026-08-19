<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\DataTransferObjects\BuiltinTypesDTO;
use Tests\TestObjects\NativeBuiltinTypes;

/**
 * Guards the invariant the engine is built on: for builtin property types,
 * ImmutableBase must accept exactly what native PHP accepts under
 * declare(strict_types=1), and must produce the same resulting type.
 *
 * Every other test in the suite asserts a hand-written expectation, which is
 * only ever as correct as the author's reading of the language. This one
 * compares against PHP itself, so a divergence — in either direction, and
 * including one introduced by a future PHP release — fails here.
 *
 * This file must keep declare(strict_types=1): the native half is decided by
 * the strict_types setting of the file performing the assignment.
 *
 * Note: PHP normalizes union member order, so `float|int` is indistinguishable
 * from `int|float` here — there is no point adding a float-first property.
 *
 * Scope: builtin types only. Do not add a property whose union contains a class,
 * enum or SVO. The engine deliberately diverges there — `int|Money` given `1`
 * builds a Money, where native PHP keeps the `int`, because PHP's normalization
 * puts class members first and PHP itself never constructs anything. That is
 * hydration doing its job, not a divergence this test should police.
 */
class NativeParityTest extends TestCase
{
    /** Valid value for every property, so one property can be varied at a time. */
    private const BASE = [
        'string'        => 'x',
        'int'           => 1,
        'float'         => 1.5,
        'bool'          => true,
        'array'         => [],
        'intOrFloat'    => 1,
        'floatOrString' => 1.5,
        'nullableFloat' => 1.5,
    ];

    /** @return array<string, mixed> */
    private static function inputs(): array
    {
        return [
            'string "1"'   => '1',
            'string "abc"' => 'abc',
            'int 1'        => 1,
            'int 0'        => 0,
            'float 1.5'    => 1.5,
            'bool true'    => true,
            'bool false'   => false,
            'array []'     => [],
            'null'         => null,
        ];
    }

    public function testBuiltinAndUnionTypesMatchNativePhp(): void
    {
        foreach (array_keys(self::BASE) as $property) {
            foreach (self::inputs() as $label => $input) {
                $this->assertSame(
                    self::nativeOutcome($property, $input),
                    self::packageOutcome($property, $input),
                    "Property \${$property} given {$label}: ImmutableBase diverges from native PHP"
                );
            }
        }
    }

    /** Outcome of handing $input to ImmutableBase: resulting type name, or 'REJECT'. */
    private static function packageOutcome(string $property, mixed $input): string
    {
        try {
            $dto = BuiltinTypesDTO::fromArray(array_merge(self::BASE, [$property => $input]));

            return get_debug_type($dto->{$property});
        } catch (\Throwable) {
            return 'REJECT';
        }
    }

    /** Outcome of assigning $input to the equivalent native typed property. */
    private static function nativeOutcome(string $property, mixed $input): string
    {
        $native = new NativeBuiltinTypes();
        try {
            $native->{$property} = $input;

            return get_debug_type($native->{$property});
        } catch (\TypeError) {
            return 'REJECT';
        }
    }
}
