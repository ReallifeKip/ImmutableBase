<?php

declare (strict_types = 1);

namespace Tests\Attacks;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use Tests\Attacks\Objects\Enums\Color;
use Tests\Attacks\Objects\Enums\EnumDTO;
use Tests\Attacks\Objects\Enums\Priority;
use Tests\Attacks\Objects\KeepOnNullDTO;
use Tests\DataTransferObjects\SkipOnNullDTO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;

class Attack3Test extends TestCase
{
    private array $baseArray;

    protected function setUp(): void
    {
        ImmutableBaseException::$depth = 0;
        ImmutableBaseException::$paths = [];

        $this->baseArray = [
            'string'              => 'string',
            'int'                 => 1,
            'float'               => 1.1,
            'bool'                => true,
            'null'                => null,
            'array'               => [1, 2, 3],
            'emptyArray'          => [],
            'union'               => 'string',
            'unionWithoutArray'   => 'string',
            'unionStringAndInt'   => 'string',
            'unionClasses'        => SVO::from(''),
            'unionSVOs'           => 'example@hotmail.com',
            'enum1'               => 'ONE',
            'enum2'               => 'one',
            'enum3'               => Enum::ONE,
            'enumMixed'           => 'string',
            'nullableString'      => 'string',
            'nullableInt'         => null,
            'nullableArray'       => null,
            'nullableFloat'       => null,
            'nullableBool'        => null,
            'nullableEnum'        => null,
            'mixed'               => 'string',
            'dataTransferObjects' => [],
            'valueObjects'        => [],
            'singleValueObjects'  => [],
        ];
    }
    public function testUnitEnumToArrayCrashes(): void
    {
        $dto = EnumDTO::fromArray(['priority' => 1, 'color' => 'RED', 'nullablePriority' => null]);
        $arr = $dto->toArray();
        $this->assertSame('RED', $arr['color']);
    }

    public function testUnitEnumToJsonCrashes(): void
    {
        $dto  = EnumDTO::fromArray(['priority' => 1, 'color' => 'RED', 'nullablePriority' => null]);
        $json = $dto->toJson();
        $data = json_decode($json, true);

        $this->assertSame(1, $data['priority']);
        $this->assertSame('RED', $data['color']);
    }

    public function testBackedEnumToArrayStillWorks(): void
    {
        $dto = EnumDTO::fromArray(['priority' => 2, 'color' => 'GREEN', 'nullablePriority' => 3]);
        $arr = $dto->toArray();
        $this->assertSame(2, $arr['priority']);
        $this->assertSame(3, $arr['nullablePriority']);
    }

    public function testToJsonRespectsSkipOnNull(): void
    {
        $dto      = SkipOnNullDTO::fromArray([]);
        $fromArr  = $dto->toArray();
        $fromJson = json_decode($dto->toJson(), true);

        $this->assertArrayNotHasKey('string', $fromArr);

        $this->assertEquals($fromArr, $fromJson);
    }

    public function testToJsonRespectsKeepOnNull(): void
    {
        $dto      = KeepOnNullDTO::fromArray([]);
        $fromArr  = $dto->toArray();
        $fromJson = json_decode($dto->toJson(), true);

        $this->assertArrayNotHasKey('skipped', $fromArr);
        $this->assertArrayHasKey('kept', $fromArr);

        $this->assertEquals($fromArr, $fromJson);
    }

    public function testSkipOnNullRoundTripViaJson(): void
    {
        $original  = SkipOnNullDTO::fromArray(['string' => 'hello']);
        $nulled    = $original->with(['string' => null]);
        $roundTrip = SkipOnNullDTO::fromJson($nulled->toJson());

        $this->assertNull($nulled->string);
        $this->assertNull($roundTrip->string);
        $this->assertEquals($nulled->toArray(), $roundTrip->toArray());
    }

    public function testUnitEnumRoundTrip(): void
    {
        $original  = EnumDTO::fromArray(['priority' => 2, 'color' => 'BLUE', 'nullablePriority' => null]);
        $arr       = $original->toArray();
        $roundTrip = EnumDTO::fromArray($arr);

        $this->assertSame(Priority::MEDIUM, $roundTrip->priority);
        $this->assertSame(Color::BLUE, $roundTrip->color);
        $this->assertNull($roundTrip->nullablePriority);
    }

    public function testUnitEnumJsonRoundTrip(): void
    {
        $original  = EnumDTO::fromArray(['priority' => 1, 'color' => 'GREEN', 'nullablePriority' => 3]);
        $json      = $original->toJson();
        $roundTrip = EnumDTO::fromJson($json);

        $this->assertSame(Priority::LOW, $roundTrip->priority);
        $this->assertSame(Color::GREEN, $roundTrip->color);
        $this->assertSame(Priority::HIGH, $roundTrip->nullablePriority);
    }
}
