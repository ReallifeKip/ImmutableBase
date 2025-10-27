<?php

namespace Tests;

use Tests\Enum;
use PHPUnit\Framework\TestCase;
use Tests\DataTransferObjects\Basic;
use Tests\DataTransferObjects\Advanced;
use Tests\DataTransferObjects\Initialized;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\DataTransferObjects\BasicFromJson;
use Tests\ExceptionObjects\ArrayOfEmptyClass;
use ReallifeKip\ImmutableBase\DataTransferObject;
use Tests\ExceptionObjects\ArrayOfNotExistsClass;
use Tests\ExceptionObjects\ShouldBePrivateButPublic;
use Tests\ExceptionObjects\ShouldBePublicButPrivate;
use ReallifeKip\ImmutableBase\Exceptions\AttributeException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidTypeException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidArrayOfClassException;
use ReallifeKip\ImmutableBase\Exceptions\NonNullablePropertyException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidPropertyVisibilityException;

class DefaultTest extends TestCase
{
    private array $nullableData;
    private array $basicData;
    private array $advancedDataByArray;
    private array $advancedDataByInstance;
    private array $modifyBasicData;
    private array $modifyAdvancedDataByArray;
    private array $modifyAdvancedDataByInstance;
    public function setup(): void
    {
        $this->nullableData = [
            'nullable_str'      => null,
            'nullable_int'      => null,
            'nullable_array'    => null,
            'nullable_object'   => null,
            'nullable_float'    => null,
            'nullable_bool'     => null,
            'nullable_enum'     => null
        ];
        $this->basicData = [
            'string'       => 'string',
            'int'       => 1,
            'array'     => [1,2,3],
            'object'    => (object)[1,2,3],
            'float'     => 1.1,
            'bool'      => true,
            'enum'      => Enum::ONE
        ] + $this->nullableData;
        $this->advancedDataByArray = $this->basicData + [
            'basic' => $this->basicData,
            'arrayOfBasics' => [
                $this->basicData,
                $this->basicData
            ],
            'union'         => 'string',
            'unionNullable' => 'string',
        ];
        $this->advancedDataByInstance = $this->basicData + [
            'basic' => new Basic($this->basicData),
            'arrayOfBasics' => [
                new Basic($this->basicData),
                new Basic($this->basicData)
            ],
            'union'         => 'string',
            'unionNullable' => 'string',
        ];
        $this->modifyBasicData = [
            'string'       => 'string_',
            'int'       => 2,
            'array'     => [4,5,6],
            'object'    => (object)[4,5,6],
            'float'     => 2.2,
            'bool'      => false,
            'enum'      => Enum::TWO,
            'nullable_str'       => 'string_',
            'nullable_int'       => 2,
            'nullable_array'     => [1,2,3],
            'nullable_object'    => (object)[1,2,3],
            'nullable_float'     => 2.2,
            'nullable_bool'      => false,
            'nullable_enum'      => Enum::TWO,
        ];
        $this->modifyAdvancedDataByArray = $this->modifyBasicData + [
            'basic' => $this->modifyBasicData,
            'arrayOfBasics' => [
                $this->modifyBasicData,
                $this->modifyBasicData
            ],
            'union' => 123,
            'unionNullable' => null
        ];
        $this->modifyAdvancedDataByInstance = $this->modifyBasicData + [
            'basic' => new Basic($this->modifyBasicData),
            'arrayOfBasics' => [
                new Basic($this->modifyBasicData),
                new Basic($this->modifyBasicData)
            ],
            'union' => 123,
            'unionNullable' => null
        ];
    }



    public function testBasic(): void
    {
        $basic = new Basic($this->basicData);
        $this->assertInstanceOf(
            Basic::class,
            $basic
        );
        $this->assertEquals(
            [1,2,3],
            $basic->array
        );
        $this->assertEquals(
            (object)[1,2,3],
            $basic->object
        );
    }
    public function testAdvancedByArray(): void
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $this->assertInstanceOf(
            Advanced::class,
            $advanced
        );
        $this->assertEquals(
            [1,2,3],
            $advanced->array
        );
        $this->assertEquals(
            (object)[1,2,3],
            $advanced->object
        );
    }
    public function testAdvancedByInstance(): void
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        $this->assertInstanceOf(
            Advanced::class,
            $advanced
        );
        $this->assertEquals(
            [1,2,3],
            $advanced->array
        );
        $this->assertEquals(
            (object)[1,2,3],
            $advanced->object
        );
    }
    public function testBasicToArray()
    {
        $basic = new Basic($this->basicData);
        $basicArray = $basic->toArray();
        $this->assertEquals(
            $this->basicData,
            $basicArray
        );
        $this->assertEquals(
            [1,2,3],
            $basicArray['array']
        );
        $this->assertEquals(
            (object)[1,2,3],
            $basicArray['object']
        );
    }
    public function testAdvancedByArrayToArray()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $advancedArray = $advanced->toArray();
        $this->assertEquals(
            $this->advancedDataByArray,
            $advancedArray
        );
        $this->assertEquals(
            [1,2,3],
            $advancedArray['array']
        );
        $this->assertEquals(
            (object)[1,2,3],
            $advancedArray['object']
        );
    }
    public function testAdvancedByInstanceToArray()
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        $advancedArray = $advanced->toArray();
        $this->assertEquals(
            array_merge(
                $this->advancedDataByInstance,
                [
                    'basic' => $this->advancedDataByInstance['basic']->toArray(),
                    'arrayOfBasics' => array_map(
                        fn ($b) => $b->toArray(),
                        $this->advancedDataByInstance['arrayOfBasics']
                    )
                ]
            ),
            $advancedArray
        );
        $this->assertEquals(
            [1,2,3],
            $advancedArray['array']
        );
        $this->assertEquals(
            (object)[1,2,3],
            $advancedArray['object']
        );
    }
    public function testBasicWith()
    {
        $basic = new Basic($this->basicData);
        $modified = $basic->with($this->modifyBasicData);
        $this->assertNotEquals(
            $basic,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
        $array = [
            'some' => 'value'
        ];
        $object = (object)[
            'string' => 'example',
            'array' => json_encode($array)
        ];
        $modified = $basic->with($object);
        $this->assertEquals(
            $object->string,
            $modified->string
        );
        $this->assertEquals(
            $array,
            $modified->array
        );
    }
    public function testBasicWithNullForNullable()
    {
        $basic = new Basic($this->basicData);
        $modified = $basic->with($this->modifyBasicData)->with($this->nullableData);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyBasicData,
                $this->nullableData
            ),
            $modifiedArray
        );
    }
    public function testAdvancedByArrayWith()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $modified = $advanced->with($this->modifyAdvancedDataByArray);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            $this->modifyAdvancedDataByArray,
            $modifiedArray
        );
        $this->assertNotEquals(
            $advanced,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->basic->object
        );
    }
    public function testAdvancedByInstanceWith()
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        $modified = $advanced->with($this->modifyAdvancedDataByInstance);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyAdvancedDataByInstance,
                [
                    'basic' => $this->modifyAdvancedDataByInstance['basic']->toArray(),
                    'arrayOfBasics' => array_map(
                        fn ($b) => $b->toArray(),
                        $this->modifyAdvancedDataByInstance['arrayOfBasics']
                    )
                ]
            ),
            $modifiedArray
        );
        $this->assertNotEquals(
            $advanced,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->basic->object
        );
    }
    public function testAdvancedByArrayWithUnionToNotBuiltin()
    {
        $basic = new Basic($this->basicData);
        $advanced = new Advanced($this->modifyAdvancedDataByArray);
        $modified = $advanced->with(['union' => $basic]);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyAdvancedDataByArray,
                ['union' => $basic->toArray()]
            ),
            $modifiedArray
        );
    }
    public function testBasicWithToArray()
    {
        $basic = new Basic($this->basicData);
        $modified = $basic->with($this->modifyBasicData);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            $this->modifyBasicData,
            $modifiedArray
        );
        $this->assertNotEquals(
            $basic,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
    }
    public function testAdvancedByArrayWithToArray()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $modified = $advanced->with($this->modifyAdvancedDataByArray);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            $this->modifyAdvancedDataByArray,
            $modifiedArray
        );
        $this->assertNotEquals(
            $advanced,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->basic->object
        );
    }
    public function testAdvancedByInstanceWithToArray()
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        $modified = $advanced->with($this->modifyAdvancedDataByInstance);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyAdvancedDataByInstance,
                [
                    'basic' => $this->modifyAdvancedDataByInstance['basic']->toArray(),
                    'arrayOfBasics' => array_map(
                        fn ($b) => $b->toArray(),
                        $this->modifyAdvancedDataByInstance['arrayOfBasics']
                    )
                ]
            ),
            $modifiedArray
        );
        $this->assertNotEquals(
            $advanced,
            $modified
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->object
        );
        $this->assertEquals(
            (object)[4,5,6],
            $modified->basic->object
        );
    }
    public function testImmutableBaseSkipsInitializedReadonlyProperties()
    {
        $this->assertEquals(
            [
                'foo' => 'foo',
            ],
            (
                new #[DataTransferObject] class () extends Initialized {
                    public function __construct()
                    {
                        parent::__construct([
                            'foo' => 'bar',
                        ]);
                    }
                }
            )->toArray()
        );
    }
    public function testEnumAcceptable()
    {
        $basic_1 = new Basic(array_merge($this->basicData, ['enum' => 'one']));
        $basic_2 = new Basic(array_merge($this->basicData, ['enum' => 'TWO']));
        $this->assertEquals(Enum::ONE, $basic_1->enum);
        $this->assertEquals(Enum::TWO, $basic_2->enum);
    }



    public function testWithBuiltinNotAllowsNullThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(NonNullablePropertyException::class);
        $this->expectExceptionMessage('Tests\DataTransferObjects\Basic string value is required and must be string.');
        $basic->with(['string' => null]);
    }
    public function testWithNotBuiltinAndNotAllowsNullThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(NonNullablePropertyException::class);
        $this->expectExceptionMessage('Tests\DataTransferObjects\Basic enum value is required and must be Tests\Enum.');
        $basic->with(['enum' => null]);
    }
    public function testValueTypeNotInUnionTypeThrowException()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Tests\DataTransferObjects\Advanced union expected types: Tests\DataTransferObjects\Basic|string|int, got double.');
        $advanced->with(['union' => 1.1]);
    }
    public function testArrayGivenButUnionTypeDoesNotIncludeThrowsException()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Tests\DataTransferObjects\Advanced union type is union and does not include array; an instantiated object is required.');
        $advanced->with(['union' => []]);
    }
    public function testWithEnumNotInCasesThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Tests\DataTransferObjects\Basic enum is Tests\Enum and does not include \'THREE\'.');
        $basic->with(['enum' => 'THREE']);
    }
    public function testArrayOfEmptyThrowException()
    {
        $this->expectException(InvalidArrayOfClassException::class);
        $this->expectExceptionMessage('Tests\ExceptionObjects\ArrayOfEmptyClass arrayOf needs to specify a target class in its #[ArrayOf] attribute.');
        new ArrayOfEmptyClass(['arrayOf' => [1]]);
    }
    public function testArrayOfClassShouldBeSubClassOfImmutableBaseThrowException()
    {
        $this->expectException(InvalidArrayOfClassException::class);
        $this->expectExceptionMessage('Tests\ExceptionObjects\ArrayOfNotExistsClass arrayOf must reference a class that extends ImmutableBase in its #[ArrayOf] attribute.');
        new ArrayOfNotExistsClass(['arrayOf' => []]);
    }
    public function testShouldHaveAttributeThrowException()
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('ImmutableBase subclasses must be annotated with either #[DataTransferObject] or #[ValueObject] or #[Entity].');
        new class () extends ImmutableBase {
        };
    }
    public function testPropertyShouldBePublicThrowException()
    {
        $this->expectException(InvalidPropertyVisibilityException::class);
        $this->expectExceptionMessage('Tests\ExceptionObjects\ShouldBePublicButPrivate string must be declared public and readonly.');
        new ShouldBePublicButPrivate();
    }
    public function testPropertyShouldNotBePublicThrowException()
    {
        $this->expectException(InvalidPropertyVisibilityException::class);
        $this->expectExceptionMessage('Tests\ExceptionObjects\ShouldBePrivateButPublic string must be declared private or protected');
        new ShouldBePrivateButPublic();
    }
    public function testUnionTypesSkipTilCorrectThrowException()
    {
        $this->expectException(InvalidTypeException::class);
        new Advanced(
            array_merge(
                $this->modifyAdvancedDataByArray,
                ['union' => 1.1]
            )
        );
    }
    public function testInvalidJsonStringForFromJsonException()
    {
        $this->expectException(InvalidJsonException::class);
        $this->expectExceptionMessage('Invalid JSON string.');
        BasicFromJson::fromJson('');
    }
}
