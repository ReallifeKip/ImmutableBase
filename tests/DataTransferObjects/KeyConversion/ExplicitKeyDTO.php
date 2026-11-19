<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKey;
use ReallifeKip\ImmutableBase\Attributes\OutputKey;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/** Keys no KeyCase can produce, mapped by exact name in both directions. */
readonly class ExplicitKeyDTO extends DataTransferObject
{
    #[InputKey('2fa'), OutputKey('2fa')]
    public bool $twoFactor;

    #[InputKey('user.id')]
    #[OutputKey('user.id')]
    public int $userId;

    /** Named argument form. */
    #[InputKey(key: '$ref')]
    public ?string $ref;
}
