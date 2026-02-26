<?php
namespace Tests\Attacks\Objects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Attacks\Objects\DeepNesting\OrderItemDTO;

readonly class NullableArrayOfDTO extends DataTransferObject
{
    #[ArrayOf(OrderItemDTO::class)]
    public ?array $items;
}
