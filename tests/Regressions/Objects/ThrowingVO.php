<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

use ReallifeKip\ImmutableBase\Objects\ValueObject;

/** A validate() that fails with a non-ImmutableBase exception. */
readonly class ThrowingVO extends ValueObject
{
    public string $name;

    public function validate(): bool
    {
        throw new \DomainException('user validator exploded');
    }
}
