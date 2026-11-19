<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Reads a property's value from the input key with exactly this name.
 *
 * Use it for keys no KeyCase can produce: keys that start with a digit,
 * contain characters such as `.` or `$`, or differ from the property name
 * entirely. The property name itself is still accepted as an input key.
 *
 * Takes precedence over #[InputKeyTo] on the same property and over the
 * class-level #[InputKeyTo]. The input key is consumed, so strict mode does
 * not report it as undeclared. Two properties of one class cannot declare
 * the same key.
 *
 * @example #[InputKey('2fa')] public bool $twoFactor;
 * @example #[InputKey('user.id')] public int $userId;
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class InputKey
{
    private function __construct(string $key)
    {
        // Prevents manual instantiation of the attribute.
    }
}
