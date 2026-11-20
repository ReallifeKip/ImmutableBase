<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Class-level OutputCaseTo(Snake) + property-level OutputCaseTo(Pascal) override.
 * - firstName → first_name  (class-level Snake)
 * - nickName  → NickName    (property-level Pascal overrides class)
 */
#[OutputCaseTo(KeyCase::Snake)]
readonly class MixedOutputKeyDTO extends DataTransferObject
{
    public string $firstName;

    /** Property-level override: serialized as PascalCase instead of snake_case */
    #[OutputCaseTo(KeyCase::Pascal)]
    public string $nickName;
}
