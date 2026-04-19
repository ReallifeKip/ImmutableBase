<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Property-level OutputKeyTo without any class-level attribute.
 * Only the decorated property is renamed on toArray(true).
 */
readonly class PropertyOutputKeyDTO extends DataTransferObject
{
    /** toArray(true) → first_name */
    #[OutputKeyTo(KeyCase::Snake)]
    public string $firstName;

    /** toArray(true) → lastName (no conversion) */
    public string $lastName;
}
