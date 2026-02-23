<?php

declare (strict_types = 1);

namespace Tests\Attacks;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use Tests\Attacks\Objects\DeepNesting\AddressDTO;
use Tests\Attacks\Objects\DeepNesting\EmailSVO;
use Tests\Attacks\Objects\DeepNesting\OrderDTO;
use Tests\Attacks\Objects\DeepNesting\OrderItemDTO;
use Tests\Attacks\Objects\DeepNesting\PersonDTO;
use Tests\Attacks\Objects\DeepNesting\SelfRefDTO;
use Tests\Attacks\Objects\DeepNesting\ValidatedOrderVO;
use Tests\Attacks\Objects\EmptyDTO;
use Tests\Attacks\Objects\Enums\IntEnumDTO;
use Tests\Attacks\Objects\Enums\IntPriority;
use Tests\Attacks\Objects\InheritedDTO;
use Tests\DataTransferObjects\DTO;
use Tests\DataTransferObjects\ExtraDTO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;
use Tests\ValueObjects\VO;

class Attack2Test extends TestCase
{
    private array $baseArray;

    protected function setUp(): void
    {
        ImmutableBaseException::$depth = 0;
        ImmutableBaseException::$paths = [];

        $this->baseArray = [
            'string'              => 'string',
            'int'                 => 1, 'float'    => 1.1,
            'bool'                => true,
            'null'                => null, 'array' => [1, 2, 3],
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

    public function testSelfRefDTOSingleLevel(): void
    {
        $dto = SelfRefDTO::fromArray(['name' => 'root', 'parent' => null]);
        $this->assertSame('root', $dto->name);
        $this->assertNull($dto->parent);
    }

    public function testSelfRefDTONested(): void
    {
        $dto = SelfRefDTO::fromArray([
            'name'   => 'child',
            'parent' => ['name' => 'parent', 'parent' => null],
        ]);
        $this->assertSame('parent', $dto->parent->name);
        $this->assertNull($dto->parent->parent);
    }

    public function testSelfRefDTODeeplyNested(): void
    {
        $data = ['name' => 'level-0', 'parent' => null];
        for ($i = 1; $i <= 10; $i++) {
            $data = ['name' => "level-$i", 'parent' => $data];
        }
        $dto = SelfRefDTO::fromArray($data);
        $this->assertSame('level-10', $dto->name);

        $cursor = $dto;
        for ($i = 9; $i >= 0; $i--) {
            $cursor = $cursor->parent;
            $this->assertSame("level-$i", $cursor->name);
        }
        $this->assertNull($cursor->parent);
    }

    public function testSelfRefDTOToArrayRoundTrip(): void
    {
        $data = [
            'name'   => 'child',
            'parent' => ['name' => 'root', 'parent' => null],
        ];
        $dto       = SelfRefDTO::fromArray($data);
        $roundTrip = SelfRefDTO::fromArray($dto->toArray());
        $this->assertSame($dto->name, $roundTrip->name);
        $this->assertSame($dto->parent->name, $roundTrip->parent->name);
    }

    public function testSelfRefDTOWithUpdatesParent(): void
    {
        $dto     = SelfRefDTO::fromArray(['name' => 'A', 'parent' => ['name' => 'B', 'parent' => null]]);
        $updated = $dto->with(['parent.name' => 'C']);
        $this->assertSame('C', $updated->parent->name);
        $this->assertSame('B', $dto->parent->name);
    }

    public function testEmptyDTOFromArray(): void
    {
        $dto = EmptyDTO::fromArray([]);
        $this->assertInstanceOf(EmptyDTO::class, $dto);
        $this->assertSame([], $dto->toArray());
    }

    public function testEmptyDTOFromArrayIgnoresExtra(): void
    {
        $dto = EmptyDTO::fromArray(['anything' => 'ignored']);
        $this->assertSame([], $dto->toArray());
    }

    public function testEmptyDTOFromJson(): void
    {
        $dto = EmptyDTO::fromJson('{}');
        $this->assertInstanceOf(EmptyDTO::class, $dto);
    }

    public function testEmptyDTOWithReturnsNewInstance(): void
    {
        $dto = EmptyDTO::fromArray([]);
        $new = $dto->with(['irrelevant' => 'data']);
        $this->assertNotSame($dto, $new);
    }

    public function testInheritedDTOHasParentProperties(): void
    {
        $dto = InheritedDTO::fromArray([
            'street'  => '123 Main',
            'city'    => 'NYC',
            'zip'     => '10001',
            'country' => 'US',
        ]);
        $this->assertSame('123 Main', $dto->street);
        $this->assertSame('US', $dto->country);
    }

    public function testInheritedDTOMissingParentPropertyThrows(): void
    {
        $this->expectException(RequiredValueException::class);
        InheritedDTO::fromArray(['country' => 'US']);
    }

    public function testInheritedDTOToArrayIncludesAll(): void
    {
        $dto = InheritedDTO::fromArray([
            'street' => 'A', 'city' => 'B', 'zip' => null, 'country' => 'C',
        ]);
        $arr = $dto->toArray();
        $this->assertArrayHasKey('street', $arr);
        $this->assertArrayHasKey('country', $arr);
    }

    public function testInheritedDTOWithUpdatesParentProperty(): void
    {
        $dto     = InheritedDTO::fromArray(['street' => 'Old', 'city' => 'X', 'zip' => null, 'country' => 'US']);
        $updated = $dto->with(['street' => 'New']);
        $this->assertSame('New', $updated->street);
        $this->assertSame('US', $updated->country);
    }

    public function testVOWithTriggeringValidationFailure(): void
    {
        $vo = VO::fromArray($this->baseArray); // string='string' → passes
        $this->expectException(ValidationChainException::class);
        $vo->with(['string' => 'invalid_value']);
    }

    public function testVOWithValidValuePasses(): void
    {
        $vo      = VO::fromArray($this->baseArray);
        $updated = $vo->with(['string' => 'string2']);
        $this->assertSame('string2', $updated->string);
    }

    public function testValidatedOrderVOPasses(): void
    {
        $vo = ValidatedOrderVO::fromArray([
            'orderId' => 'V-1',
            'items'   => [['sku' => 'A', 'quantity' => 2, 'price' => 5.0]],
        ]);
        $this->assertSame('V-1', $vo->orderId);
    }

    public function testValidatedOrderVOZeroTotalFails(): void
    {
        $this->expectException(ValidationChainException::class);
        ValidatedOrderVO::fromArray([
            'orderId' => 'V-2',
            'items'   => [['sku' => 'A', 'quantity' => 0, 'price' => 5.0]],
        ]);
    }

    public function testValidatedOrderVOEmptyItemsFails(): void
    {
        $this->expectException(ValidationChainException::class);
        ValidatedOrderVO::fromArray([
            'orderId' => 'V-3',
            'items'   => [],
        ]);
    }

    public function testIntEnumZeroValue(): void
    {
        $dto = IntEnumDTO::fromArray(['priority' => 0, 'optional' => null]);
        $this->assertSame(IntPriority::LOW, $dto->priority);
    }

    public function testIntEnumViaWith(): void
    {
        $dto     = IntEnumDTO::fromArray(['priority' => 0, 'optional' => null]);
        $updated = $dto->with(['priority' => 2]);
        $this->assertSame(IntPriority::HIGH, $updated->priority);
    }

    public function testEnumInstancePassedToWith(): void
    {
        $dto     = DTO::fromArray($this->baseArray);
        $updated = $dto->with(['enum1' => Enum::TWO]);
        $this->assertSame(Enum::TWO, $updated->enum1);
    }

    public function testEnumToArraySerializesBackedValue(): void
    {
        $dto = DTO::fromArray($this->baseArray);
        $arr = $dto->toArray();
        $this->assertSame('one', $arr['enum1']);
    }

    public function testNullableEnumNullInToArray(): void
    {
        $dto = DTO::fromArray($this->baseArray);
        $arr = $dto->toArray();
        $this->assertNull($arr['nullableEnum']);
    }

    public function testToJsonEqualsToArray(): void
    {
        $dto      = DTO::fromArray($this->baseArray);
        $fromArr  = $dto->toArray();
        $fromJson = json_decode($dto->toJson(), true);
        $this->assertEquals($fromArr, $fromJson);
    }

    public function testDeepToJsonEqualsToArray(): void
    {
        $data = [
            'orderId'  => 'ORD-1',
            'customer' => [
                'name'    => 'A', 'email' => 'a@b.com',
                'address' => ['street' => 'S', 'city' => 'C', 'zip' => null],
            ],
            'items'    => [['sku' => 'X', 'quantity' => 1, 'price' => 2.5]],
            'note'     => 'test',
        ];
        $order    = OrderDTO::fromArray($data);
        $fromArr  = $order->toArray();
        $fromJson = json_decode($order->toJson(), true);
        $this->assertEquals($fromArr, $fromJson);
    }

    public function testWithSwitchesTypeInUnion(): void
    {
        $dto = DTO::fromArray($this->baseArray);
        $this->assertIsString($dto->union);

        $updated = $dto->with(['union' => 42]);
        $this->assertIsInt($updated->union);
        $this->assertSame(42, $updated->union);
    }

    public function testWithSwitchesUnionFromIntToArray(): void
    {
        $dto     = DTO::fromArray(array_merge($this->baseArray, ['union' => 99]));
        $updated = $dto->with(['union' => [1, 2, 3]]);
        $this->assertIsArray($updated->union);
    }

    public function testWithFourLevelDeepPath(): void
    {
        $extra = ExtraDTO::fromArray($this->baseArray + [
            'string2'       => 's',
            'dto'           => array_merge($this->baseArray, [
                'unionClasses' => $this->baseArray,
            ]),
            'unionClasses2' => SVO::from(''),
        ]);
        $updated = $extra->with(['dto.unionClasses.string' => 'string2']);
        $this->assertSame('string2', $updated->dto->unionClasses->string);
    }

    public function testFromArrayWithPrebuiltNestedObject(): void
    {
        $address = AddressDTO::fromArray(['street' => 'Pre', 'city' => 'Built', 'zip' => null]);
        $person  = PersonDTO::fromArray([
            'name'    => 'Test',
            'email'   => EmailSVO::from('a@b.com'),
            'address' => $address,
        ]);
        $this->assertSame('Pre', $person->address->street);
        $this->assertSame($address, $person->address);
    }

    public function testFromArrayPrebuiltAndRawMixed(): void
    {
        $order = OrderDTO::fromArray([
            'orderId'  => 'ORD-MIX',
            'customer' => PersonDTO::fromArray([
                'name'    => 'A', 'email' => 'a@b.com',
                'address' => ['street' => 'S', 'city' => 'C', 'zip' => null],
            ]),
            'items'    => [
                ['sku' => 'RAW', 'quantity' => 1, 'price' => 1.0],
                OrderItemDTO::fromArray(['sku' => 'PRE', 'quantity' => 2, 'price' => 3.0]),
            ],
            'note'     => null,
        ]);
        $this->assertSame('A', $order->customer->name);
        $this->assertSame('RAW', $order->items[0]->sku);
        $this->assertSame('PRE', $order->items[1]->sku);
    }

    public function testSVOWithTriggeringValidation(): void
    {
        $this->expectException(ValidationChainException::class);
        $mail = EmailSVO::from('valid@email.com');
        $mail->with('no-at-sign');
    }

    public function testFromArrayIgnoresIntegerKeys(): void
    {
        $dto = DTO::fromArray($this->baseArray);
        $this->assertInstanceOf(DTO::class, $dto);
    }

    public function testWithJsonStringForNestedObject(): void
    {
        $extra = ExtraDTO::fromArray($this->baseArray + [
            'string2'       => 's',
            'dto'           => $this->baseArray,
            'unionClasses2' => SVO::from(''),
        ]);
        $json    = json_encode(['string' => 'fromJson'] + $this->baseArray);
        $updated = $extra->with(['dto' => $json]);
        $this->assertSame('fromJson', $updated->dto->string);
    }

    public function testLargeArrayOf(): void
    {
        $items = array_fill(0, 500, ['sku' => 'X', 'quantity' => 1, 'price' => 1.0]);
        $order = OrderDTO::fromArray([
            'orderId'  => 'BIG',
            'customer' => [
                'name'    => 'A', 'email' => 'a@b.com',
                'address' => ['street' => 'S', 'city' => 'C', 'zip' => null],
            ],
            'items'    => $items,
            'note'     => null,
        ]);
        $this->assertCount(500, $order->items);
    }

    public function testNullTypePropertyAcceptsNull(): void
    {
        $dto = DTO::fromArray($this->baseArray);
        $this->assertNull($dto->null);
    }

    public function testNullTypePropertyRejectsNonNull(): void
    {
        $this->expectException(ImmutableBaseException::class);
        DTO::fromArray(array_merge($this->baseArray, ['null' => 'not null']));
    }

    public function testDoubleFromArrayProducesSameResult(): void
    {
        $a = DTO::fromArray($this->baseArray);
        $b = DTO::fromArray($this->baseArray);
        $this->assertEquals($a->toArray(), $b->toArray());
        $this->assertNotSame($a, $b);
    }
}
