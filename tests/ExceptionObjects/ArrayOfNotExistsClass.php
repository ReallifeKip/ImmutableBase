<?php

declare(strict_types=1);

namespace Tests\ExceptionObjects;

use ReallifeKip\ImmutableBase\ArrayOf;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class ArrayOfNotExistsClass extends ImmutableBase
{
    #[ArrayOf('not_exist_class')]
    public readonly array $arrayOf;
}
