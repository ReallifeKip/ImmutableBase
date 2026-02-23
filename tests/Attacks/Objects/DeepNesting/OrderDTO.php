<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\Attacks\Objects\DeepNesting\PersonDTO;

readonly class OrderDTO extends DataTransferObject
{
    public string $orderId;
    public PersonDTO $customer;
    #[ArrayOf(OrderItemDTO::class)]
    public array $items;
    public ?string $note;
}
