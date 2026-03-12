<?php
declare (strict_types = 1);

namespace Tests\Attacks;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidArrayOfTargetException;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidPropertyTypeException;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidEnumValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidValueException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\Attacks\Objects\DeepNesting\AddressDTO;
use Tests\Attacks\Objects\DeepNesting\CompanyDTO;
use Tests\Attacks\Objects\DeepNesting\OrderDTO;
use Tests\Attacks\Objects\DeepNesting\OrderItemDTO;
use Tests\Attacks\Objects\Enums\Color;
use Tests\Attacks\Objects\Enums\EnumDTO;
use Tests\Attacks\Objects\Enums\Priority;
use Tests\Attacks\Objects\ForbiddenTypes\ClosurePropertyDTO;
use Tests\Attacks\Objects\ForbiddenTypes\DateTimePropertyDTO;
use Tests\Attacks\Objects\ForbiddenTypes\InterfacePropertyDTO;
use Tests\Attacks\Objects\ForbiddenTypes\ObjectPropertyDTO;
use Tests\Attacks\Objects\ForbiddenTypes\UnionWithForeignClassDTO;
use Tests\Attacks\Objects\ForbiddenTypes\UnionWithObjectDTO;
use Tests\Attacks\Objects\KeepOnNullDTO;
use Tests\Attacks\Objects\NullableArrayOfDTO;
use Tests\DataTransferObjects\DTO;
use Tests\DataTransferObjects\ExtraDTO;
use Tests\DataTransferObjects\InvalidTypeArrayOfDTO;
use Tests\DataTransferObjects\StrictDTO;
use Tests\SingleValueObjects\GmailSVO;
use Tests\SingleValueObjects\MailSVO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;
use Tests\ValueObjects\VO;

class AttackTest extends TestCase
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

    public function testForbiddenType_Object(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        ObjectPropertyDTO::fromArray(['payload' => new \stdClass()]);
    }

    public function testForbiddenType_DateTime(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        DateTimePropertyDTO::fromArray(['createdAt' => '2025-01-01']);
    }

    public function testForbiddenType_Interface(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        InterfacePropertyDTO::fromArray(['label' => 'hello']);
    }

    public function testForbiddenType_Closure(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        ClosurePropertyDTO::fromArray(['handler' => fn() => null]);
    }

    public function testForbiddenType_UnionWithObject(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        UnionWithObjectDTO::fromArray(['value' => 'hello']);
    }

    public function testForbiddenType_UnionWithForeignClass(): void
    {
        $this->expectException(InvalidPropertyTypeException::class);
        UnionWithForeignClassDTO::fromArray(['value' => 'hello']);
    }

    public function testArrayOfOnStringProperty(): void
    {
        $this->expectException(InvalidArrayOfTargetException::class);
        InvalidTypeArrayOfDTO::fromArray(['regulars' => []]);
    }

    public function testArrayOfWithInvalidItem_IntInsteadOfDTO(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'dataTransferObjects' => [42],
        ]));
    }

    public function testArrayOfWithInvalidItem_BoolInsteadOfDTO(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'dataTransferObjects' => [false],
        ]));
    }

    public function testArrayOfWithInvalidItem_NullInsteadOfDTO(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'dataTransferObjects' => [null],
        ]));
    }

    public function testArrayOfWithMixedValidAndInvalid(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'dataTransferObjects' => [
                $this->baseArray,
                42,
            ],
        ]));
    }

    public function testArrayOfWithInvalidJsonString(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'dataTransferObjects' => ['not-json-at-all'],
        ]));
    }

    public function testKeepOnNullPreservesNullInToArray(): void
    {
        $dto    = KeepOnNullDTO::fromArray([]);
        $result = $dto->toArray();

        $this->assertArrayNotHasKey('skipped', $result);
        $this->assertArrayHasKey('kept', $result);
        $this->assertNull($result['kept']);
        $this->assertArrayNotHasKey('alsoSkipped', $result);
    }

    public function testWithReturnsNewInstance(): void
    {
        $original = DTO::fromArray($this->baseArray);
        $modified = $original->with(['string' => 'changed']);

        $this->assertNotSame($original, $modified);
        $this->assertSame('string', $original->string);
        $this->assertSame('changed', $modified->string);
    }

    public function testWithDoesNotMutateNestedObjects(): void
    {
        $extra = ExtraDTO::fromArray($this->baseArray + [
            'string2'       => 'original',
            'dto'           => $this->baseArray,
            'unionClasses2' => SVO::from('x'),
        ]);

        $modified = $extra->with(['dto.string' => 'changed']);
        $this->assertSame('string', $extra->dto->string);
        $this->assertSame('changed', $modified->dto->string);
    }

    public function testSVOWithReturnsNewSVO(): void
    {
        $svo = SVO::from('original');
        $new = $svo->with('replaced');

        $this->assertInstanceOf(SVO::class, $new);
        $this->assertSame('replaced', $new->value);
        $this->assertSame('original', $svo->value);
    }

    public function testSVOWithSameInstancePassthrough(): void
    {
        $svo    = SVO::from('hello');
        $result = $svo->with($svo);
        $this->assertSame($svo, $result);
    }

    public function testMailSVOWithNewValue(): void
    {
        $mail = MailSVO::from('a@b.com');
        $new  = $mail->with('x@y.com');
        $this->assertSame('x@y.com', $new->value);
    }

    public function testDeepNestedErrorPath_Level2(): void
    {
        try {
            OrderDTO::fromArray([
                'orderId'  => 'ORD-1',
                'customer' => [
                    'name'    => 'Alice',
                    'email'   => 'not-an-email', // EmailSVO validate() fails
                    'address' => ['street' => '123 Main', 'city' => 'NYC', 'zip' => null],
                ],
                'items'    => [],
            ]);
            $this->fail('Should have thrown');
        } catch (ImmutableBaseException $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('OrderDTO', $msg);
            $this->assertStringContainsString('customer', $msg);
        }
    }

    public function testDeepNestedErrorPath_MissingRequiredInNested(): void
    {
        try {
            OrderDTO::fromArray([
                'orderId'  => 'ORD-1',
                'customer' => [
                    'name'    => 'Alice',
                    'address' => ['street' => '123 Main', 'city' => 'NYC'],
                ],
                'items'    => [],
            ]);
            $this->fail('Should have thrown');
        } catch (RequiredValueException $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('email', $msg);
        }
    }

    public function testDeepNestedErrorPath_Level3ArrayOf(): void
    {
        try {
            CompanyDTO::fromArray([
                'name'      => 'Acme',
                'employees' => [
                    [
                        'name'    => 'Bob',
                        'email'   => 'bob@x.com',
                        'address' => [
                            'street' => '1 St',
                        ],
                    ],
                ],
            ]);
            $this->fail('Should have thrown');
        } catch (RequiredValueException $e) {
            $msg = $e->getMessage();
            $this->assertStringContainsString('city', $msg);
        }
    }

    public function testErrorPathStaticStateResetsAfterException(): void
    {
        try {
            DTO::fromArray(array_merge($this->baseArray, ['string' => null]));
        } catch (RequiredValueException) {
        }

        $this->assertSame(0, ImmutableBaseException::$depth);
        $this->assertEmpty(ImmutableBaseException::$paths);

        try {
            DTO::fromArray(array_merge($this->baseArray, ['int' => []]));
        } catch (InvalidValueException $e) {
            $this->assertStringNotContainsString('string', $e->getMessage());
        }
    }

    public function testErrorPathResetsAfterDeepNestedException(): void
    {
        try {
            ExtraDTO::fromArray([
                'string2'       => 's',
                'dto'           => array_merge($this->baseArray, ['string' => null]),
                'unionClasses2' => SVO::from(''),
            ]);
        } catch (RequiredValueException) {
            // Silent for the next Exception
        }

        $this->assertSame(0, ImmutableBaseException::$depth);
        $this->assertEmpty(ImmutableBaseException::$paths);

        try {
            DTO::fromArray(array_merge($this->baseArray, ['float' => 'wrong']));
        } catch (InvalidValueException $e) {
            $this->assertStringNotContainsString('dto', $e->getMessage());
            $this->assertStringNotContainsString('ExtraDTO', $e->getMessage());
        }
    }

    public function testIntBackedEnum(): void
    {
        $dto = EnumDTO::fromArray(['priority' => 2, 'color' => 'GREEN', 'nullablePriority' => null]);
        $this->assertSame(Priority::MEDIUM, $dto->priority);
    }

    public function testUnitEnumByName(): void
    {
        $dto = EnumDTO::fromArray(['priority' => 1, 'color' => 'RED', 'nullablePriority' => null]);
        $this->assertSame(Color::RED, $dto->color);
    }

    public function testUnitEnumInvalidName(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        EnumDTO::fromArray(['priority' => 1, 'color' => 'YELLOW', 'nullablePriority' => null]);
    }

    public function testNullableEnumNull(): void
    {
        $dto = EnumDTO::fromArray(['priority' => 1, 'color' => 'RED', 'nullablePriority' => null]);
        $this->assertNull($dto->nullablePriority);
    }

    public function testIntGivenToFloatProperty(): void
    {
        $this->expectException(InvalidValueException::class);
        DTO::fromArray(array_merge($this->baseArray, ['float' => 1]));
    }

    public function testFloatGivenToIntProperty(): void
    {
        $this->expectException(InvalidValueException::class);
        DTO::fromArray(array_merge($this->baseArray, ['int' => 1.5]));
    }

    public function testStringGivenToIntProperty(): void
    {
        $this->expectException(InvalidValueException::class);
        DTO::fromArray(array_merge($this->baseArray, ['int' => '42']));
    }

    public function testIntZeroIsValid(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, ['int' => 0]));
        $this->assertSame(0, $dto->int);
    }

    public function testEmptyStringIsValid(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, ['string' => '']));
        $this->assertSame('', $dto->string);
    }

    public function testFalseIsValidBool(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, ['bool' => false]));
        $this->assertFalse($dto->bool);
    }

    public function testWithInvalidJsonStringThrows(): void
    {
        $this->expectException(InvalidJsonException::class);
        $dto = DTO::fromArray($this->baseArray);
        $dto->with('not json');
    }

    public function testWithEmptySeparatorDisablesDeepPath(): void
    {
        $extra = ExtraDTO::fromArray($this->baseArray + [
            'string2'       => 's',
            'dto'           => $this->baseArray,
            'unionClasses2' => SVO::from(''),
        ]);
        $result = $extra->with(['dto.string' => 'x'], '');
        $this->assertSame('string', $result->dto->string);
    }

    public function testWithNonExistentPropertySilentlyIgnored(): void
    {
        $dto    = DTO::fromArray($this->baseArray);
        $result = $dto->with(['nonExistent' => 'value']);
        $this->assertSame($dto->string, $result->string);
    }

    public function testWithReplacesArrayOfContent(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, [
            'singleValueObjects' => [SVO::from('a'), SVO::from('b')],
        ]));
        $updated = $dto->with(['singleValueObjects' => [SVO::from('c')]]);
        $this->assertCount(1, $updated->singleValueObjects);
        $this->assertSame('c', $updated->singleValueObjects[0]->value);
    }

    public function testGlobalStrictThrowsOnNormalDTO(): void
    {
        ImmutableBase::strict(true);
        try {
            $this->expectException(StrictViolationException::class);
            DTO::fromArray($this->baseArray + ['extraField' => 'boom']);
        } finally {
            ImmutableBase::strict(false);
        }
    }

    public function testGlobalStrictMultipleRedundantKeys(): void
    {
        ImmutableBase::strict(true);
        try {
            StrictDTO::fromArray(['string' => null, 'a' => 1, 'b' => 2, 'c' => 3]);
        } catch (StrictViolationException $e) {
            $this->assertStringContainsString('a', $e->getMessage());
            $this->assertStringContainsString('b', $e->getMessage());
            $this->assertStringContainsString('c', $e->getMessage());
        } finally {
            ImmutableBase::strict(false);
        }
    }

    public function testFromArrayEmptyWithRequiredProperties(): void
    {
        $this->expectException(RequiredValueException::class);
        AddressDTO::fromArray([]);
    }

    public function testFromJsonWithArrayJsonThrows(): void
    {
        $this->expectException(RequiredValueException::class);
        AddressDTO::fromJson('[1,2,3]');
    }

    public function testDeepRoundTrip(): void
    {
        $data = [
            'orderId'  => 'ORD-99',
            'customer' => [
                'name'    => 'Alice',
                'email'   => 'alice@example.com',
                'address' => ['street' => '1 Main St', 'city' => 'NYC', 'zip' => '10001'],
            ],
            'items'    => [
                ['sku' => 'A', 'quantity' => 2, 'price' => 9.99],
                ['sku' => 'B', 'quantity' => 1, 'price' => 4.50],
            ],
            'note'     => null,
        ];
        $order     = OrderDTO::fromArray($data);
        $roundTrip = OrderDTO::fromArray($order->toArray());

        $this->assertSame($order->orderId, $roundTrip->orderId);
        $this->assertSame($order->customer->email->value, $roundTrip->customer->email->value);
        $this->assertCount(2, $roundTrip->items);
        $this->assertSame('B', $roundTrip->items[1]->sku);
    }

    public function testDeepToJsonRoundTrip(): void
    {
        $data = [
            'orderId'  => 'ORD-1',
            'customer' => [
                'name'    => 'Bob',
                'email'   => 'bob@x.com',
                'address' => ['street' => 'A', 'city' => 'B', 'zip' => null],
            ],
            'items'    => [['sku' => 'X', 'quantity' => 1, 'price' => 1.0]],
            'note'     => 'rush',
        ];
        $order = OrderDTO::fromArray($data);
        $json  = $order->toJson();
        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertSame('ORD-1', $decoded['orderId']);
        $this->assertSame('bob@x.com', $decoded['customer']['email']);
    }

    public function testNullableArrayOfWithNull(): void
    {
        $dto = NullableArrayOfDTO::fromArray(['items' => null]);
        $this->assertNull($dto->items);
    }

    public function testNullableArrayOfWithData(): void
    {
        $dto = NullableArrayOfDTO::fromArray([
            'items' => [['sku' => 'A', 'quantity' => 1, 'price' => 5.0]],
        ]);
        $this->assertCount(1, $dto->items);
        $this->assertInstanceOf(OrderItemDTO::class, $dto->items[0]);
    }

    public function testUnionClassesResolvesToFirstMatch(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, ['unionClasses' => $this->baseArray]));
        $this->assertInstanceOf(VO::class, $dto->unionClasses);
    }

    public function testUnionSVOsResolvesCorrectly(): void
    {
        $dto = DTO::fromArray(array_merge($this->baseArray, [
            'unionSVOs' => 'test@gmail.com',
        ]));
        $this->assertInstanceOf(GmailSVO::class, $dto->unionSVOs);
    }

    public function testUnionSVOsBothFail(): void
    {
        $this->expectException(ImmutableBaseException::class);
        DTO::fromArray(array_merge($this->baseArray, [
            'unionSVOs' => 'test@yahoo.com',
        ]));
    }

    public function testWithDeepPathIntoArrayOfItem(): void
    {
        $order = OrderDTO::fromArray([
            'orderId'  => 'ORD-1',
            'customer' => [
                'name'    => 'A', 'email' => 'a@b.com',
                'address' => ['street' => 'S', 'city' => 'C', 'zip' => null],
            ],
            'items'    => [
                ['sku' => 'OLD', 'quantity' => 1, 'price' => 10.0],
            ],
            'note'     => null,
        ]);

        $updated = $order->with(['items.0.sku' => 'NEW']);
        $this->assertSame('NEW', $updated->items[0]->sku);
        $this->assertSame('OLD', $order->items[0]->sku);
    }

    public function testMixedAcceptsAnything(): void
    {
        foreach ([42, 3.14, true, null, 'str', [1, 2], new \stdClass()] as $value) {
            $dto = DTO::fromArray(array_merge($this->baseArray, ['mixed' => $value]));
            $this->assertEquals($value, $dto->mixed);
        }
    }

    public function testMissingKeyThrowsRequiredValue(): void
    {
        $data = $this->baseArray;
        unset($data['string']);

        $this->expectException(RequiredValueException::class);
        DTO::fromArray($data);
    }

    public function testExplicitNullOnNonNullableThrows(): void
    {
        $this->expectException(RequiredValueException::class);
        DTO::fromArray(array_merge($this->baseArray, ['string' => null]));
    }

    public function testMissingNullableKeyYieldsNull(): void
    {
        $data = $this->baseArray;
        unset($data['nullableString']);
        $dto = DTO::fromArray($data);
        $this->assertNull($dto->nullableString);
    }
}
