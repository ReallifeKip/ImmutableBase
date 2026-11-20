<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Class-level OutputCaseTo(Snake): toArray(true) converts all property
 * keys to snake_case. toArray(false) leaves keys as-is.
 */
#[OutputCaseTo(KeyCase::Snake)]
readonly class ClassOutputSnakeDTO extends DataTransferObject
{
    public string $firstName;
    public string $lastName;
    public int $userAge;
}
