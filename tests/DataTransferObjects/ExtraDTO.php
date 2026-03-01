<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\SingleValueObjects\SVO;
use Tests\ValueObjects\VO;

readonly class ExtraDTO extends DataTransferObject
{
    public string $string2;
    public DTO $dto;
    public VO|SVO $unionClasses2;
}
