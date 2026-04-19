<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

use ReallifeKip\ImmutableBase\Enums\KeyCase;

/**
 * Converts property keys to a specified naming convention on serialization
 * (toArray() / toJson() called with true).
 *
 * When applied to a class, all property keys are converted to the target case
 * during serialization. When applied to a property, overrides any class-level
 * conversion for that property only.
 *
 * Has no effect when toArray() / toJson() is called with false (default)
 * or with an explicit KeyCase::* (which forces a global override).
 *
 * @example #[OutputKeyTo(KeyCase::Snake)]  — serializes nickName as nick_name
 * @example #[OutputKeyTo(KeyCase::Camel)]  — serializes nick_name as nickName
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class OutputKeyTo
{
    private function __construct(KeyCase $keyCase)
    {
        // Prevents manual instantiation of the attribute.
    }
}
