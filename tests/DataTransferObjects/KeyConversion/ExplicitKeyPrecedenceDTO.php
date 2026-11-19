<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKey;
use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\OutputKey;
use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\Strict;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** #[InputKey] / #[OutputKey] next to property- and class-level KeyCase conversion, under strict mode. */
#[Strict, InputKeyTo(KeyCase::Camel), OutputKeyTo(KeyCase::Snake)]
readonly class ExplicitKeyPrecedenceDTO extends DataTransferObject
{
    public string $firstName;

    #[InputKey('x-api-key'), InputKeyTo(KeyCase::Macro)]
    #[OutputKey('X-Api-Key'), OutputKeyTo(KeyCase::Kebab)]
    public string $apiKey;
}
