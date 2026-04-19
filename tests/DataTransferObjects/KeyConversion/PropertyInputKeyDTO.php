<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Property-level InputKeyTo without any class-level attribute.
 *
 * Semantic: #[InputKeyTo(X)] on property $propName means "convert each input key
 * to case X — if the result equals the property name, use that value".
 * Therefore the property name MUST already be in case X format.
 *
 *   $firstName  is Camel → #[InputKeyTo(Camel)] accepts: first_name, first-name, FIRST_NAME …
 *   $last_name  is Snake → #[InputKeyTo(Snake)] accepts: lastName, LAST_NAME, Last-Name …
 *   $city       has no annotation → only the exact key 'city' is accepted
 */
readonly class PropertyInputKeyDTO extends DataTransferObject
{
    /** Property is camelCase → InputKeyTo(Camel) converts any input key to Camel to match. */
    #[InputKeyTo(KeyCase::Camel)]
    public string $firstName;

    /** Property is snake_case → InputKeyTo(Snake) converts any input key to Snake to match. */
    #[InputKeyTo(KeyCase::Snake)]
    public string $last_name;

    /** No annotation — only key 'city' accepted. */
    public string $city;
}
