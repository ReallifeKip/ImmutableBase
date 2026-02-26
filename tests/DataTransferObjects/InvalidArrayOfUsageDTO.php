<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;

readonly class InvalidArrayOfUsageDTO extends DTO
{
    #[ArrayOf(DTO::class)]
    public string $dtos;
}
