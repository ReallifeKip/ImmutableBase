<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\SingleValueObjects\SVO;

readonly class WithJsonDTO extends DataTransferObject
{
    public string $note;
    public mixed $meta;
    public array $raw;
    public array|int $rawOrInt;
    public string|array $textOrList;
    public ?SVO $label;
    #[ArrayOf(PathItemDTO::class)]
    public array $items;
}
