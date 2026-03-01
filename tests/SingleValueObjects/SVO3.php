<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;

#[ValidateFromSelf]
readonly class SVO3 extends SVO2
{
    public function validate(): bool
    {
        echo "SVO_3\n";

        return true;
    }
}
