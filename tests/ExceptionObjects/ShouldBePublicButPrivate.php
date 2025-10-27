<?php

declare(strict_types=1);

namespace Tests\ExceptionObjects;

use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class ShouldBePublicButPrivate extends ImmutableBase
{
    private readonly string $string;
}
