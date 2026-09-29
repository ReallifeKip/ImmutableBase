<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidEnumValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use Tests\Regressions\Objects\CodeItemDTO;
use Tests\Regressions\Objects\Level;
use Tests\Regressions\Objects\TrueDTO;
use Tests\Regressions\Objects\TypeErrorFreeDTO;
use Tests\SingleValueObjects\SVO;

/**
 * Invalid input must always surface as an ImmutableBaseException subclass,
 * never as a PHP TypeError from a raw property assignment or a mistyped
 * internal call. TypeError also bypassed union member fallback.
 */
class TypeErrorTest extends TestCase
{
    private const BASE = [
        'svoOrInt'      => null,
        'items'         => null,
        'nullableUnion' => null,
        'list'          => null,
        'levels'        => null,
        'level'         => null,
        'stringOrFalse' => null,
    ];

    private static function make(array $override): TypeErrorFreeDTO
    {
        return TypeErrorFreeDTO::fromArray($override + self::BASE);
    }

    public function testSvoFromWrongScalarType(): void
    {
        $this->expectException(InvalidValueException::class);
        SVO::from(123);
    }

    public function testSvoFromNull(): void
    {
        $this->expectException(RequiredValueException::class);
        SVO::from(null);
    }

    public function testSvoFromObject(): void
    {
        $this->expectException(InvalidValueException::class);
        SVO::from(new \stdClass());
    }

    public function testUnionFallsThroughSvoMemberToInt(): void
    {
        $this->assertSame(5, self::make(['svoOrInt' => 5])->svoOrInt);
        $this->assertInstanceOf(SVO::class, self::make(['svoOrInt' => 'x'])->svoOrInt);
    }

    public function testUnionFallsThroughStrictMemberToNextMember(): void
    {
        $this->assertInstanceOf(CodeItemDTO::class, self::make(['items' => ['code' => 'c']])->items);
    }

    public function testUnionWithNullMemberRejectsUnmatchedArray(): void
    {
        $this->expectException(InvalidValueException::class);
        self::make(['nullableUnion' => ['unknown' => 1]]);
    }

    public function testArrayOfRejectsScalar(): void
    {
        $this->expectException(InvalidValueException::class);
        self::make(['list' => 5]);
    }

    public function testArrayOfEnumRejectsNonScalarItem(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        self::make(['levels' => [true]]);
    }

    public function testArrayOfEnumRejectsFloatItem(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        self::make(['levels' => [1.5]]);
    }

    public function testEnumNonCaseConstantIsNotACase(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        self::make(['level' => 'LIMIT']);
    }

    public function testEnumCaseAliasConstantStillResolves(): void
    {
        $this->assertSame(Level::Low, self::make(['level' => 'DEFAULT'])->level);
    }

    public function testFalseLiteralMemberAcceptsFalse(): void
    {
        $this->assertFalse(self::make(['stringOrFalse' => false])->stringOrFalse);
        $this->assertSame('s', self::make(['stringOrFalse' => 's'])->stringOrFalse);
    }

    public function testFalseLiteralMemberRejectsTrue(): void
    {
        $this->expectException(InvalidValueException::class);
        self::make(['stringOrFalse' => true]);
    }

    public function testStandaloneTrueType(): void
    {
        $this->assertTrue(TrueDTO::fromArray(['flag' => true])->flag);
        $this->expectException(InvalidValueException::class);
        TrueDTO::fromArray(['flag' => false]);
    }

    public function testNoTypeErrorEscapesForAnyScalarAgainstAnyProperty(): void
    {
        $inputs = [0, 1, 1.5, true, false, '', 'x', '[x', '{}', [], [1], ['a' => 1], new \stdClass()];
        foreach (array_keys(self::BASE) as $property) {
            foreach ($inputs as $input) {
                try {
                    self::make([$property => $input]);
                } catch (ImmutableBaseException) {
                } catch (\TypeError $e) {
                    $this->fail("TypeError for \$$property given " . get_debug_type($input) . ': ' . $e->getMessage());
                }
            }
        }
        $this->addToAssertionCount(1);
    }
}
