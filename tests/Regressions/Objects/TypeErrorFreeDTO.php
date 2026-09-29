<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\SingleValueObjects\SVO;

readonly class TypeErrorFreeDTO extends DataTransferObject
{
    public SVO|int|null $svoOrInt;
    public StrictItemDTO|CodeItemDTO|null $items;
    public PathItemDTO|CodeItemDTO|null $nullableUnion;
    #[ArrayOf(PathItemDTO::class)]
    public ?array $list;
    #[ArrayOf(Level::class)]
    public ?array $levels;
    public ?Level $level;
    public string|false|null $stringOrFalse;
}
