<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKey;
use ReallifeKip\ImmutableBase\Attributes\InputCaseTo;
use ReallifeKip\ImmutableBase\Attributes\OutputKey;
use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** #[InputKey] / #[OutputKey] next to property- and class-level KeyCase conversion, under strict mode. */
#[Strict, InputCaseTo(KeyCase::Camel), OutputCaseTo(KeyCase::Snake)]
readonly class ExplicitKeyPrecedenceDTO extends DataTransferObject
{
    public string $firstName;

    #[InputKey('x-api-key'), InputCaseTo(KeyCase::Macro)]
    #[OutputKey('X-Api-Key'), OutputCaseTo(KeyCase::Kebab)]
    public string $apiKey;
}
