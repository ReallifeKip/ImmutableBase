<?php

declare (strict_types = 1);

namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class TargetDTO extends DataTransferObject
{
    public int $id;
    public string $name;
    public string $email;
    public bool $isActive;
    public float $score;
}
