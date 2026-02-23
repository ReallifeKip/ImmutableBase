<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

readonly class SVO_2 extends SVO_1
{
    public function validate(): bool
    {
        echo "SVO_2\n";

        return true;
    }
}
