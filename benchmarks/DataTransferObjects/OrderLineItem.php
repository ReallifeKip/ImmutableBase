<?php

declare (strict_types = 1);

namespace Benchmarks\DataTransferObjects;

use Benchmarks\SingleValueObjects\Price;
use Benchmarks\SingleValueObjects\Sku;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class OrderLineItem extends DataTransferObject
{
    public Sku $sku;
    public string $productName;
    public int $quantity;
    public Price $unitPrice;
    public Price $totalPrice;
}
