<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Reverses the validation chain traversal order for a ValueObject.
 *
 * By default, validation walks from the root ancestor down to the
 * concrete class (bottom-up declaration order). With this attribute,
 * validation starts from the concrete class and walks upward.
 *
 * Useful when a child class's validate() has preconditions that should
 * be checked before parent validators execute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValidateFromSelf
{
    private function __construct()
    {
        // Prevents manual instantiation of the attribute.
    }
}
