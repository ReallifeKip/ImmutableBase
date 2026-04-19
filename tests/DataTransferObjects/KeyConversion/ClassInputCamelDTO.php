<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Class-level InputKeyTo(Camel): accepts snake_case input keys,
 * converting them to camelCase before matching against property names.
 */
#[InputKeyTo(KeyCase::Camel)]
readonly class ClassInputCamelDTO extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
    public int $userAge;
}
