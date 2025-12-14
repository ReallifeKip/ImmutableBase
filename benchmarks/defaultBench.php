<?php

declare(strict_types=1);

namespace Benchmarks;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** @BeforeMethods({"setUp"}) */
class defaultBench
{
    private array $simpleData;
    private array $nestedData;
    private array $collectionData_100;
    private array $collectionData_1000;
    private array $recursiveData_10;
    private array $recursiveData_50;
    private string $jsonString;

    public function setUp(): void
    {
        $this->simpleData = [
            'name' => 'Kip',
            'age' => 30,
            'active' => true,
            'score' => 99.5
        ];

        $this->nestedData = [
            'simple' => $this->simpleData,
            'type' => 'A'
        ];
        $this->collectionData_100 = [
            'list' => array_fill(0, 100, $this->simpleData)
        ];
        $this->collectionData_1000 = [
            'list' => array_fill(0, 1000, $this->simpleData)
        ];
        $this->recursiveData_10 = $this->buildRecursiveData(10);
        $this->recursiveData_50 = $this->buildRecursiveData(50);
        $this->jsonString = json_encode($this->simpleData);
    }
    private function buildRecursiveData(int $depth): array
    {
        $current = null;
        for ($i = $depth; $i > 0; $i--) {
            $current = [
                'id' => $i,
                'child' => $current
            ];
        }
        return $current;
    }
    public function benchGentleSimpleFromArray(): void
    {
        SimpleDTO::fromArray($this->simpleData);
    }
    public function benchGentleSimpleFromJson(): void
    {
        SimpleDTO::fromJson($this->jsonString);
    }
    public function benchModerateNested(): void
    {
        NestedDTO::fromArray($this->nestedData);
    }
    public function benchModerateToArray(): void
    {
        $dto = NestedDTO::fromArray($this->nestedData);
        $dto->toArray();
    }
    public function benchModerateWith(): void
    {
        $dto = NestedDTO::fromArray($this->nestedData);
        $dto->with([
            'simple' => [
                'name' => 'New Name'
            ]
        ]);
    }
    public function benchViolentCollection_100(): void
    {
        CollectionDTO::fromArray($this->collectionData_100);
    }
    public function benchViolentCollection_1000(): void
    {
        CollectionDTO::fromArray($this->collectionData_1000);
    }
    public function benchHellRecursion_10(): void
    {
        RecursiveDTO::fromArray($this->recursiveData_10);
    }
    public function benchHellRecursion_50(): void
    {
        RecursiveDTO::fromArray($this->recursiveData_50);
    }
    public function benchHellRecursionToArray_50(): void
    {
        $dto = RecursiveDTO::fromArray($this->recursiveData_50);
        $dto->toArray();
    }
}
enum BenchEnum: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
}

class SimpleDTO extends DataTransferObject
{
    public readonly string $name;
    public readonly int $age;
    public readonly bool $active;
    public readonly ?float $score;
}

class NestedDTO extends DataTransferObject
{
    public readonly SimpleDTO $simple;
    public readonly BenchEnum $type;
}

class CollectionDTO extends DataTransferObject
{
    #[ArrayOf(SimpleDTO::class)]
    public readonly array $list;
}

class RecursiveDTO extends DataTransferObject
{
    public readonly int $id;
    public readonly ?RecursiveDTO $child;
}
