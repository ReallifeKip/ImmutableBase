<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Class-level OutputKeyTo(Snake) + property-level OutputKeyTo(Pascal) override.
 * - firstName → first_name  (class-level Snake)
 * - nickName  → NickName    (property-level Pascal overrides class)
 */
#[OutputKeyTo(KeyCase::Snake)]
readonly class MixedOutputKeyDTO extends DataTransferObject
{
    public string $firstName;

    /** Property-level override: serialized as PascalCase instead of snake_case */
    #[OutputKeyTo(KeyCase::Pascal)]
    public string $nickName;
}
