<?php

declare (strict_types = 1);
namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class SimpleDTO extends DataTransferObject
{
    public string $name;
    public int $age;
    public bool $isActive;
}
