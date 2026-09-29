<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class DocPairFirst extends DataTransferObject
{
    public string $first;

    public function anonymous(): object
    {
        return new class () {
            public function name(): string
            {
                return 'not a class declaration';
            }
        };
    }
}

readonly class DocPairSecond extends DataTransferObject
{
    public string $second;
}
