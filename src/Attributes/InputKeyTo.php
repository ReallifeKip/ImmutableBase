<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

use ReallifeKip\ImmutableBase\Enums\KeyCase;

/**
 * Converts input array keys to a specified naming convention before hydration.
 *
 * When applied to a class, all input keys are converted to the target case
 * before being matched against property names.
 *
 * When applied to a property, overrides any class-level conversion for that
 * specific property only.
 *
 * Conversion splits words on camelCase/PascalCase boundaries, underscores,
 * hyphens, and whitespace, then rejoins in the target case.
 *
 * @example #[InputKeyTo(KeyCase::Camel)]  — accepts snake_case input keys
 * @example #[InputKeyTo(KeyCase::Snake)]  — accepts camelCase input keys
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class InputKeyTo
{
    private function __construct(KeyCase $keyCase)
    {
        // Prevents manual instantiation of the attribute.
    }
}
