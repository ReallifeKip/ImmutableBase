<?php
namespace Tests\Attacks\Objects\Enums;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class EnumDTO extends DataTransferObject
{
    public Priority $priority;
    public Color $color;
    public ?Priority $nullablePriority;
}
