<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use Tests\DataTransferObjects\UnionClassOrderDTO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;

/**
 * Locks the union member priority the README documents as a design decision.
 *
 * Native PHP never builds anything from a scalar — `int|Money` given `1` keeps
 * the `int`, and only an already-constructed Money becomes a Money. Hydration
 * is what creates the ambiguity, so this package answers it: a class, Enum or
 * SVO member is attempted before a builtin one. NativeParityTest deliberately
 * excludes class members for exactly this reason; the rules live here instead.
 *
 * Two separate guarantees are asserted:
 *   1. Class members outrank builtin members, wherever they appear in the
 *      declaration — PHP normalizes builtins to the back.
 *   2. Among class members, the declaration order is preserved and decides the
 *      winner — this is the one part of union priority the declaring code
 *      controls, so it must not regress.
 */
class UnionPriorityTest extends TestCase
{
    private const BASE = ['enumFirst' => 'one', 'svoFirst' => 'one'];

    public function testClassMemberOrderDecidesWhichClassWins(): void
    {
        $dto = UnionClassOrderDTO::fromArray(self::BASE);
        $this->assertInstanceOf(Enum::class, $dto->enumFirst);
        $this->assertInstanceOf(SVO::class, $dto->svoFirst);
    }

    public function testClassMembersOutrankBuiltinMembers(): void
    {
        $dto = UnionClassOrderDTO::fromArray(self::BASE);
        $this->assertIsNotString($dto->enumFirst, 'string was declared first yet must lose to a class member');
        $this->assertIsNotString($dto->svoFirst, 'string was declared first yet must lose to a class member');
    }

    public function testAlreadyConstructedInstancePassesThroughUntouched(): void
    {
        $svo = SVO::from('one');
        $dto = UnionClassOrderDTO::fromArray(['enumFirst' => 'one', 'svoFirst' => $svo]);
        $this->assertSame($svo, $dto->svoFirst);
        $this->assertInstanceOf(SingleValueObject::class, $dto->svoFirst);
    }
}
