<?php

declare (strict_types = 1);
namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class NestedDTO extends DataTransferObject
{
    public string $title;

    #[ArrayOf(SimpleDTO::class)]
    public array $items;
}
