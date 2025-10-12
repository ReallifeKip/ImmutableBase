<?php

namespace Tests;

use Exception;
use Tests\Enum;
use PHPUnit\Framework\TestCase;
use Tests\DataTransferObjects\Basic;
use ReallifeKip\ImmutableBase\ArrayOf;
use Tests\DataTransferObjects\Advanced;
use Tests\DataTransferObjects\Initialized;
use ReallifeKip\ImmutableBase\ValueObject;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

class defaultTest extends TestCase
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

    /**
     * 驗證 Basic 類別可由 array 正確建立。
     * 驗證建立後屬性型別與值（例如 array 與 object 欄位）。
     */
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
    /**
     * 驗證 Advanced 可由巢狀 array 建構。
     * 驗證繼承自 Basic 的屬性可被初始化。
     */
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
    /**
     * 驗證 Advanced 可由巢狀的 DTO/instance 建構（即 basic 與 arrayOfBasics 為實例）。
     * 驗證 Advanced 屬性順序與初始資料順序一致。
     */
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
    /**
     * 驗證 Basic->toArray() 與初始資料一致。
     */
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
    /**
     * 驗證 Advanced->toArray() 與初始資料一致。
     */
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
    /**
     * 驗證 Advanced->toArray() 時，巢狀 instance 可被初始化。
     */
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
    /**
     * 驗證 Basic->with() 資料修改並回傳新的實例。
     * 驗證覆寫結果與覆寫資料一致。
     */
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
    }
    /**
     * 驗證可鏈式呼叫 with()
     * 驗證 null 更新 nullable 欄位。
     * 驗證 nullable 欄位經更新後，於 toArray() 中變更為 null。
     */
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
    /**
     * 驗證 Advanced->with() 使用 array 修改值。
     * 驗證巢狀 Basic 欄位也會被正確變更。
     */
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
    /**
     * 驗證 Advanced->with() 可接受巢狀 instance 修改並返回新實例。
     */
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
    /**
     * 驗證 Basic->with()->toArray() 與修改資料一致。
     */
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
    /**
     * 驗證 Advanced->with()->toArray() 與修改資料一致。
     */
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
    /**
     * 驗證 Advanced->with()->toArray() 與修改資料一致。
     */
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
    /**
     * 驗證當子類含有已初始化的 readonly property 時，跳過並保留父類已初始化的值。
     */
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
    /**
     * 驗證 Enum 型別可接受 key value 形式。
     */
    public function testEnumAcceptable()
    {
        $basic_1 = new Basic(array_merge($this->basicData, ['enum' => 'one']));
        $basic_2 = new Basic(array_merge($this->basicData, ['enum' => 'TWO']));
        $this->assertEquals(Enum::ONE, $basic_1->enum);
        $this->assertEquals(Enum::TWO, $basic_2->enum);
    }

    /**
     * 驗證非 nullable 欄位嘗試傳入 null 拋出 Exception。
     */
    public function testWithBuiltinNotAllowsNullThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('string 型別錯誤，期望：string，傳入：NULL');
        $basic->with(['string' => null]);
    }
    /**
     * 驗證非內建型別且非 nullable 欄位嘗試傳入 null 拋出 Exception。
     */
    public function testWithNotBuiltinAndNotAllowsNullThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('enum 型別錯誤，期望：Tests\Enum，傳入：NULL');
        $basic->with(['enum' => null]);
    }
    /**
     * 驗證 union 欄位傳入不被允許的型別（例如 double）拋出 Exception。
     */
    public function testValueTypeNotInUnionTypeThrowException()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('union 型別錯誤，期望：string|int，傳入：double');
        $advanced->with(['union' => 1.1]);
    }
    /**
     * 驗證 union 不包含 array 時傳入 array 拋出 Exception。
     */
    public function testArrayGivenButUnionTypeDoesNotIncludeThrowsException()
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('型別為複合且不包含array，須傳入已實例化的物件。');
        $advanced->with(['union' => []]);
    }
    /**
     * 驗證 enum cases 不包含傳入值時拋出 Exception。
     */
    public function testWithEnumNotInCasesThrowException()
    {
        $basic = new Basic($this->basicData);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('enum THREE 不是 Tests\Enum 的期望值');
        $basic->with(['enum' => 'THREE']);
    }
    /**
     * 驗證 ArrayOf attribute 未指定 class 拋出 Exception。
     */
    public function testArrayOfEmptyThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ArrayOf class 不能為空');
        new #[DataTransferObject] class () extends ImmutableBase {
            #[ArrayOf()]
            public readonly array $arrayOf;
        };
    }
    /**
     * 驗證 ArrayOf 指定的 class 不存在或不是 ImmutableBase 的子類時拋出 Exception。
     */
    public function testArrayOfClassShouldBeSubClassOfImmutableBaseThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('arrayOf ArrayOf 指定的 class 必須為 ImmutableBase 的子類');
        new #[DataTransferObject] class () extends ImmutableBase {
            #[ArrayOf('not_exist_class')]
            public readonly array $arrayOf;
        };
    }
    /**
     * 驗證子類未標註 DataTransferObject/ValueObject/Entity 時拋出 Exception。
     */
    public function testShouldHaveAttributeThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('ImmutableBase 子類必須使用 DataTransferObject、ValueObject 或 Entity 任一標註');
        new class () extends ImmutableBase {
        };
    }
    /**
     * 驗證 DataTransferObject property 不是 public readonly 時拋出 Exception。
     */
    public function testPropertyShouldBePublicThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('string 必須為 public 且 readonly');
        new #[DataTransferObject] class () extends ImmutableBase {
            private readonly string $string;
        };
    }
    /**
     * 驗證 ValueObject 屬性為 public 時應拋出 Exception。
     */
    public function testPropertyShouldNotBePublicThrowException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('string 不允許為 public');
        new #[ValueObject] class () extends ImmutableBase {
            public string $string;
        };
    }
}
