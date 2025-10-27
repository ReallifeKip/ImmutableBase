<?php

namespace Tests;

use Tests\Enum;
use PHPUnit\Framework\TestCase;
use Tests\DataTransferObjects\NewBasic;
use Tests\DataTransferObjects\NewAdvanced;
use Tests\DataTransferObjects\BasicFromJson;
use Tests\DataTransferObjects\AdvancedFromJson;

class NewTest extends TestCase
{
    private array $nullableDataArray;
    private string $nullableDataJson;
    private array $basicDataArray;
    private string $basicDataJson;
    private array $advancedDataArrayByArray;
    private string $advancedDataJsonByArray;
    private array $modifyBasicDataArray;
    private string $modifyBasicDataJson;
    private array $modifyAdvancedDataArrayByArray;
    private string $modifyAdvancedDataJsonByArray;
    public function setup(): void
    {
        $this->nullableDataArray = [
            'nullable_str'      => null,
            'nullable_int'      => null,
            'nullable_array'    => null,
            'nullable_float'    => null,
            'nullable_bool'     => null,
            'nullable_enum'     => null
        ];
        $this->nullableDataJson = json_encode($this->nullableDataArray);
        $this->basicDataArray = [
            'string'       => 'string',
            'int'       => 1,
            'array'     => [1,2,3],
            'float'     => 1.1,
            'bool'      => true,
            'enum'      => Enum::ONE
        ] + $this->nullableDataArray;
        $this->basicDataJson = json_encode($this->basicDataArray);
        $basicFromArray = NewBasic::fromArray($this->basicDataArray);
        $basicFromJson = NewBasic::fromJson($this->basicDataJson);
        $this->advancedDataArrayByArray = $this->basicDataArray + [
            'basic' => $this->basicDataArray,
            'arrayOfBasics' => [
                $this->basicDataArray,
                $this->basicDataJson,
                $basicFromArray,
                $basicFromJson
            ],
            'union'         => 'string',
            'unionNullable' => 'string',
        ];
        $this->advancedDataJsonByArray = json_encode($this->advancedDataArrayByArray);
        $this->modifyBasicDataArray = [
            'string'       => 'string_',
            'int'       => 2,
            'array'     => [4,5,6],
            'float'     => 2.2,
            'bool'      => false,
            'enum'      => Enum::TWO,
            'nullable_str'       => 'string_',
            'nullable_int'       => 2,
            'nullable_array'     => [1,2,3],
            'nullable_float'     => 2.2,
            'nullable_bool'      => false,
            'nullable_enum'      => Enum::TWO,
        ];
        $this->modifyBasicDataJson = json_encode($this->modifyBasicDataArray);
        $modifyBasicFromArray = $basicFromArray->with($this->modifyBasicDataArray);
        $modifyBasicFromJson = $basicFromJson->with($this->modifyBasicDataJson);
        $this->modifyAdvancedDataArrayByArray = $this->modifyBasicDataArray + [
            'basic' => $this->modifyBasicDataArray,
            'arrayOfBasics' => [
                $this->modifyBasicDataArray,
                $this->modifyBasicDataJson,
                $modifyBasicFromArray,
                $modifyBasicFromJson
            ],
            'union' => 123,
            'unionNullable' => null
        ];
        $this->modifyAdvancedDataJsonByArray = json_encode($this->modifyAdvancedDataArrayByArray);
    }



    public function testBasicFromJson()
    {
        $basic = BasicFromJson::fromJson($this->basicDataJson);
        $this->assertInstanceOf(
            BasicFromJson::class,
            $basic
        );
        $this->assertEquals(
            [1,2,3],
            $basic->array
        );
    }
    public function testBasicFromArray()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $this->assertInstanceOf(
            NewBasic::class,
            $basic
        );
        $this->assertEquals(
            [1,2,3],
            $basic->array
        );
    }
    public function testAdvancedFromJson()
    {
        $advanced = AdvancedFromJson::fromJson($this->advancedDataJsonByArray);
        $this->assertInstanceOf(
            AdvancedFromJson::class,
            $advanced
        );
        $this->assertEquals(
            [1,2,3],
            $advanced->array
        );
    }
    public function testAdvancedFromArray()
    {
        $advanced = NewAdvanced::fromArray($this->advancedDataArrayByArray);
        $this->assertInstanceOf(
            NewAdvanced::class,
            $advanced
        );
        $this->assertEquals(
            [1,2,3],
            $advanced->array
        );
    }
    public function testBasicFromJsonToArray()
    {
        $basic = NewBasic::fromJson($this->basicDataJson);
        $basicArray = $basic->toArray();
        $this->assertEquals(
            $this->basicDataJson,
            json_encode($basicArray)
        );
        $this->assertEquals(
            [1,2,3],
            $basicArray['array']
        );
    }
    public function testBasicFromArrayToArray()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $basicArray = $basic->toArray();
        $this->assertEquals(
            $this->basicDataArray,
            $basicArray
        );
        $this->assertEquals(
            [1,2,3],
            $basicArray['array']
        );
    }
    public function testAdvancedFromJsonByArrayToArray()
    {
        $advanced = NewAdvanced::fromJson($this->advancedDataJsonByArray);
        $advancedArray = $advanced->toArray();
        $advancedDataJsonByArray = json_decode($this->advancedDataJsonByArray, true);
        $arrayOfBasics = $advancedDataJsonByArray['arrayOfBasics'];
        $this->assertEquals(
            $this->advancedDataJsonByArray,
            json_encode($advancedDataJsonByArray)
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[0]),
            json_encode($arrayOfBasics[0])
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[1]),
            $arrayOfBasics[1]
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[2]),
            json_encode($arrayOfBasics[2])
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[3]),
            json_encode($arrayOfBasics[3])
        );
        $this->assertEquals(
            [1,2,3],
            $advancedArray['array']
        );
    }
    public function testAdvancedFromArrayByArrayToArray()
    {
        $advanced = NewAdvanced::fromArray($this->advancedDataArrayByArray);
        $advancedArray = $advanced->toArray();
        $advancedDataArrayByArray = $this->advancedDataArrayByArray;
        $arrayOfBasics = $advancedDataArrayByArray['arrayOfBasics'];
        $this->assertEquals(
            $this->advancedDataArrayByArray,
            $advancedDataArrayByArray
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[0]),
            json_encode($arrayOfBasics[0])
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[1]),
            $arrayOfBasics[1]
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[2]),
            json_encode($arrayOfBasics[2])
        );
        $this->assertEquals(
            json_encode($advanced->arrayOfBasics[3]),
            json_encode($arrayOfBasics[3])
        );
        $this->assertEquals(
            [1,2,3],
            $advancedArray['array']
        );
    }
    public function testBasicFromJsonWithJson()
    {
        $basic = NewBasic::fromJson($this->basicDataJson);
        $modified = $basic->with($this->modifyBasicDataJson);
        $this->assertNotEquals(
            $basic,
            $modified
        );
    }
    public function testBasicFromJsonWithArray()
    {
        $basic = NewBasic::fromJson($this->basicDataJson);
        $modified = $basic->with($this->modifyBasicDataArray);
        $this->assertNotEquals(
            $basic,
            $modified
        );
    }
    public function testBasicFromArrayWithJson()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $modified = $basic->with($this->modifyBasicDataJson);
        $this->assertNotEquals(
            $basic,
            $modified
        );
    }
    public function testBasicFromArrayWithArray()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $modified = $basic->with($this->modifyBasicDataArray);
        $this->assertNotEquals(
            $basic,
            $modified
        );
    }
    public function testBasicFromJsonWithNullForNullableJson()
    {
        $basic = NewBasic::fromJson($this->basicDataJson);
        $modified = $basic->with($this->modifyBasicDataArray)->with($this->nullableDataJson);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyBasicDataArray,
                json_decode($this->nullableDataJson, true)
            ),
            $modifiedArray
        );
    }
    public function testBasicFromJsonWithNullForNullableArray()
    {
        $basic = NewBasic::fromJson($this->basicDataJson);
        $modified = $basic->with($this->modifyBasicDataArray)->with($this->nullableDataArray);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyBasicDataArray,
                $this->nullableDataArray
            ),
            $modifiedArray
        );
    }
    public function testBasicFromArrayWithNullForNullableJson()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $modified = $basic->with($this->modifyBasicDataArray)->with($this->nullableDataJson);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyBasicDataArray,
                json_decode($this->nullableDataJson, true)
            ),
            $modifiedArray
        );
    }
    public function testBasicFromArrayWithNullForNullableArray()
    {
        $basic = NewBasic::fromArray($this->basicDataArray);
        $modified = $basic->with($this->modifyBasicDataArray)->with($this->nullableDataArray);
        $modifiedArray = $modified->toArray();
        $this->assertEquals(
            array_merge(
                $this->modifyBasicDataArray,
                $this->nullableDataArray
            ),
            $modifiedArray
        );
    }
}
