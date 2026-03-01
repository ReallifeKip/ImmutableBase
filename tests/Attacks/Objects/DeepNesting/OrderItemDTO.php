<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class OrderItemDTO extends DataTransferObject
{
    public string $sku;
    public int $quantity;
    public float $price;
}
