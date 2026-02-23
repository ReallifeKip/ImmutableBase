<?php
namespace Tests\Attacks\Objects\ForbiddenTypes;

use DateTimeImmutable;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class UnionWithForeignClassDTO extends DataTransferObject
{
    public string|DateTimeImmutable $value;
}
