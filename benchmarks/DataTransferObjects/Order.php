<?php

declare (strict_types = 1);

namespace Benchmarks\DataTransferObjects;

use Benchmarks\Enums\OrderStatus;
use Benchmarks\ValueObjects\Customer;
use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class Order extends DataTransferObject
{
    public string $id;
    public OrderStatus $status;
    public Customer $customer;

    #[ArrayOf(OrderLineItem::class)]
    public array $items;

    public array|null $metadata;
}
