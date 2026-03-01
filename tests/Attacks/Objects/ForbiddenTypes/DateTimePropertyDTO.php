<?php
namespace Tests\Attacks\Objects\ForbiddenTypes;

use DateTimeImmutable;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DateTimePropertyDTO extends DataTransferObject
{
    public DateTimeImmutable $createdAt;
}
