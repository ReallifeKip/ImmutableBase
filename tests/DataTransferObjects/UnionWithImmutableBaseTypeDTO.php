<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\ValueObjects\VO;

readonly class UnionWithImmutableBaseTypeDTO extends DataTransferObject
{
    public DTO|VO $mixed;
}
