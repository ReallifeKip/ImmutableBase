<?php

declare (strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidCompareTargetException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use Tests\DataTransferObjects\DTO1;
use Tests\DataTransferObjects\DTO2;
use Tests\TestObjects\Enum1;
use Tests\TestObjects\Enum;
use Tests\ValueObjects\ProfileVO;
use Tests\ValueObjects\VO;

class ValueObjectTest extends TestCase
{
    private array $array;
    private string $json;
    private array $profile;
    public function setup(): void
    {
        $this->array = [
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
            'unionClasses'        => DTO1::fromArray(['string1' => 'string1']),
            'enum1'               => 'ONE',
            'enum2'               => 'one',
            'enum3'               => Enum::ONE,
            'enumMixed'           => Enum1::ONE,
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
        $this->json    = json_encode($this->array);
        $this->profile = [
            'name'  => 'example',
            'age'   => 20,
            'email' => 'example@gmail.com',
        ];
    }
    public function testBasic()
    {
        $vo  = VO::fromArray($this->array);
        $vo2 = VO::fromJson($this->json);
        $this->assertTrue($vo->equals($vo2));

        $vo3 = VO::fromArray(['unionClasses' => VO::fromArray($this->array)] + $this->array);
        $this->assertFalse($vo->equals($vo3));

        $vo4 = $vo2->with(['string' => 'string2']);
        $this->assertFalse($vo->equals($vo4));

        $vo5 = VO::fromArray($this->array)->with(['enumMixed' => Enum1::TWO]);
        $this->assertFalse($vo5->equals(VO::fromArray($this->array)));
    }
    public function testValidateFailedGetSpec()
    {
        try {
            VO::fromArray(array_merge($this->array, ['string' => 'example'], ));
        } catch (ValidationChainException $e) {
            $this->assertEquals('Validate failed.', $e->getSpec());
        }
    }
    public function testInvalidCompareTargetThrowException()
    {
        $this->expectException(InvalidCompareTargetException::class);
        $this->expectExceptionMessage('equals() expects an instance of ' . VO::class);
        VO::fromArray($this->array)->equals(ProfileVO::fromArray($this->profile));
    }
    public function testValidateFailedThrowValidationChainException()
    {
        $this->expectException(ValidationChainException::class);
        $this->expectExceptionMessage('Object of class Tests\ValueObjects\VO is not validated Reason: Validate failed.');
        VO::fromArray(array_merge($this->array, ['string' => 'example'], ));
    }
    public function testEqualsCompareDifferentEnumThrowException()
    {
        $vo = VO::fromArray($this->array)->with(['unionClasses' => DTO2::fromArray(['string2' => 'string2'])]);
        $this->assertFalse($vo->equals(VO::fromArray($this->array)));
    }
}
