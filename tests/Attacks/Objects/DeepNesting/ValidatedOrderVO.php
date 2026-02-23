<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

#[Spec('Order total must be positive.')]
readonly class ValidatedOrderVO extends ValueObject
{
    public string $orderId;
    #[ArrayOf(OrderItemDTO::class)]
    public array $items;
    public function validate(): bool
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->quantity * $item->price;
        }

        return $total > 0;
    }
}
