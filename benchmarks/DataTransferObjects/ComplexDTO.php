<?php

declare (strict_types = 1);
namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class ComplexDTO extends DataTransferObject
{
    public string|int $id;
    public ?float $score;
    public array $metadata;
}
