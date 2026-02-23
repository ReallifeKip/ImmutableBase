<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class SelfRefDTO extends DataTransferObject
{
    public string $name;
    public ?SelfRefDTO $parent;
}
