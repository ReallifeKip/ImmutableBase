<?php
namespace Tests\Attacks\Objects\Enums;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class IntEnumDTO extends DataTransferObject
{
    public IntPriority $priority;
    public ?IntPriority $optional;
}
