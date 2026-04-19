<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Class-level InputKeyTo(Snake) handles snake_case properties.
 * Property $USER_ID is MACRO_CASE — the class-level Snake would convert
 * e.g. 'userId' → 'user_id' which does NOT match 'USER_ID'.
 * Property-level InputKeyTo(Macro) overrides: converts any input key to Macro
 * and matches against 'USER_ID'.
 *
 *   $user_name: class Snake — accepts: userName, user-name, USER_NAME …
 *   $USER_ID:   property Macro — accepts: userId, user_id, user-id … → MACRO → USER_ID
 */
#[InputKeyTo(KeyCase::Snake)]
readonly class MixedInputKeyDTO extends DataTransferObject
{
    public string $user_name;

    /** Property is MACRO_CASE → InputKeyTo(Macro) accepts any input that converts to USER_ID. */
    #[InputKeyTo(KeyCase::Macro)]
    public string $USER_ID;
}
