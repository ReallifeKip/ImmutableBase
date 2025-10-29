<?php

declare(strict_types=1);

namespace Tests\ExceptionObjects;

use ReallifeKip\ImmutableBase\ArrayOf;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class ArrayOfEmptyClass extends ImmutableBase
{
    #[ArrayOf('')]
    public readonly array $arrayOf;
}
