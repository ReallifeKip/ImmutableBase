<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Property-level InputCaseTo without any class-level attribute.
 *
 * Semantic: #[InputCaseTo(X)] on property $propName means "convert each input key
 * to case X — if the result equals the property name, use that value".
 * Therefore the property name MUST already be in case X format.
 *
 *   $firstName  is Camel → #[InputCaseTo(Camel)] accepts: first_name, first-name, FIRST_NAME …
 *   $last_name  is Snake → #[InputCaseTo(Snake)] accepts: lastName, LAST_NAME, Last-Name …
 *   $city       has no annotation → only the exact key 'city' is accepted
 */
readonly class PropertyInputKeyDTO extends DataTransferObject
{
    /** Property is camelCase → InputCaseTo(Camel) converts any input key to Camel to match. */
    #[InputCaseTo(KeyCase::Camel)]
    public string $firstName;

    /** Property is snake_case → InputCaseTo(Snake) converts any input key to Snake to match. */
    #[InputCaseTo(KeyCase::Snake)]
    public string $last_name;

    /** No annotation — only key 'city' accepted. */
    public string $city;
}
