<?php

declare (strict_types = 1);
namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[OutputKeyTo(KeyCase::Snake)]
readonly class OutputKeyDTO extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
    public string $emailAddress;
    public int $accountId;
    public bool $isActive;
    public string $createdAt;
    public string $updatedAt;
}
