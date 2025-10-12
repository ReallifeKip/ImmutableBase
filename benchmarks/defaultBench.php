<?php

declare(strict_types=1);

namespace Benchmarks;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Tests\Enum;
use Tests\DataTransferObjects\Basic;
use Tests\DataTransferObjects\Advanced;

final class defaultBench
{
    private array $nullableData;
    private array $basicData;
    private array $advancedDataByArray;
    private array $advancedDataByInstance;
    private array $modifyBasicData;
    private array $modifyAdvancedDataByArray;
    private array $modifyAdvancedDataByInstance;
    public function __construct()
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
     * 建立給定深度的深層巢狀 Advanced 陣列結構（用於測試深層建構與遞迴處理）。
     */
    private function buildDeepArray(int $depth = 5): array
    {
        $base = $this->basicData;
        $base['basic'] = $this->basicData;
        $base['arrayOfBasics'] = [$this->basicData];
        $base['union'] = 'string';
        $base['unionNullable'] = 'string';

        $current = $base;
        for ($i = 1; $i < $depth; $i++) {
            $current = $this->basicData;
            $current['basic'] = $current;
            $current['arrayOfBasics'] = [$current];
            $current['union'] = 'string';
            $current['unionNullable'] = 'string';
        }

        return $current;
    }

    /**
     * 建立給定深度的深層巢狀 Advanced 實例結構（由底層陣列遞迴轉換為 Advanced 實例）。
     */
    private function buildDeepInstance(int $depth = 5): Advanced
    {
        $base = $this->basicData;
        $base['basic'] = $this->basicData;
        $base['arrayOfBasics'] = [$this->basicData];
        $base['union'] = 'string';
        $base['unionNullable'] = 'string';

        $current = $base;
        for ($i = 1; $i < $depth; $i++) {
            $current = $this->basicData;
            $current['arrayOfBasics'] = [$current];
            $current['basic'] = $current instanceof Basic ? $current : $current;
            $current['union'] = 'string';
            $current['unionNullable'] = 'string';
        }

        $convert = function ($node) use (&$convert) {
            if (is_array($node) && isset($node['basic'])) {
                $node['basic'] = $convert($node['basic']);
                if (is_array($node['arrayOfBasics'])) {
                    $node['arrayOfBasics'] = array_map($convert, $node['arrayOfBasics']);
                }
                return new Advanced($node);
            }
            return $node;
        };

        return $convert($current);
    }

    /**
     * 從深層陣列（深度 5）建立 Advanced，測量深層建構的成本與行為。
     */
    public function benchConstructDeepAdvancedFromArray(): void
    {
        $deep = $this->buildDeepArray(5);
        new Advanced($deep);
    }

    /**
     * 從深層實例建立 Advanced（先生成深層實例再由其陣列重建），用以測量實例->陣列->重建 的成本。
     */
    public function benchConstructDeepAdvancedFromInstance(): void
    {
        $deep = $this->buildDeepInstance();
        new Advanced($deep->toArray());
    }

    /**
     * 深層 Advanced 執行 toArray()，測量遞迴序列化深層巢狀結構的效能與輸出正確性。
     */
    public function benchToArrayDeepAdvanced(): array
    {
        $deep = $this->buildDeepInstance();
        return $deep->toArray();
    }

    /**
     * 在深層 Advanced 上執行 with()，測量複製與巢狀欄位覆寫在深層結構上的成本。
     */
    public function benchWithDeepAdvanced(): void
    {
        $deep = $this->buildDeepInstance();
        $deep->with($deep->toArray());
    }

    /**
     * 從陣列建立 Basic 的建構效能測試（測量單層 DTO 建構成本）。
     */
    public function benchConstructBasicFromArray(): void
    {
        new Basic($this->basicData);
    }

    /**
     * 從巢狀陣列建立 Advanced 的建構效能測試（測量解析巢狀陣列為 DTO 的成本）。
     */
    public function benchConstructAdvancedFromArray(): void
    {
        new Advanced($this->advancedDataByArray);
    }

    /**
     * 從巢狀實例建立 Advanced 的建構效能測試（測量接受已初始化 instance 的情況）。
     */
    public function benchConstructAdvancedFromInstance(): void
    {
        new Advanced($this->advancedDataByInstance);
    }

    /**
     * 在 Basic 上呼叫 toArray()，測量單層 DTO 序列化為陣列的成本。
     */
    public function benchToArrayBasic(): array
    {
        $basic = new Basic($this->basicData);
        return $basic->toArray();
    }

    /**
     * 在由陣列建立的 Advanced 上呼叫 toArray()，測量巢狀 DTO 轉陣列的效能。
     */
    public function benchToArrayAdvancedByArray(): array
    {
        $advanced = new Advanced($this->advancedDataByArray);
        return $advanced->toArray();
    }

    /**
     * 在由實例建立的 Advanced 上呼叫 toArray()，測量巢狀實例序列化的效能。
     */
    public function benchToArrayAdvancedByInstance(): array
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        return $advanced->toArray();
    }

    /**
     * 在 Basic 上執行 with()，測量建立新 immutable 實例並覆寫單層欄位的成本。
     */
    public function benchWithBasic(): void
    {
        $basic = new Basic($this->basicData);
        $basic->with($this->modifyBasicData);
    }

    /**
     * 在由陣列建立的 Advanced 上執行 with()，測量巢狀欄位覆寫與新實例建立的成本。
     */
    public function benchWithAdvancedByArray(): void
    {
        $advanced = new Advanced($this->advancedDataByArray);
        $advanced->with($this->modifyAdvancedDataByArray);
    }

    /**
     * 在由實例建立的 Advanced 上執行 with()，測量接受巢狀 instance 作為修改來源時的成本。
     */
    public function benchWithAdvancedByInstance(): void
    {
        $advanced = new Advanced($this->advancedDataByInstance);
        $advanced->with($this->modifyAdvancedDataByInstance);
    }
}
