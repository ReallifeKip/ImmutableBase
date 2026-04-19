<?php

declare (strict_types = 1);
namespace Benchmarks\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

#[InputKeyTo(KeyCase::Camel)]
readonly class InputKeyDTO extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
    public string $emailAddress;
    public int $accountId;
    public bool $isActive;
    public string $createdAt;
    public string $updatedAt;
}
