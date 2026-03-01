<?php
namespace Tests\Attacks\Objects\ForbiddenTypes;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class UnionWithObjectDTO extends DataTransferObject
{
    public string|object $value;
}
