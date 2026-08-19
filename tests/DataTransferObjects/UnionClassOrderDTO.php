<?php

declare (strict_types = 1);

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum;

/**
 * Both properties accept the string 'one' as an Enum case value *and* as an SVO
 * value, so which member wins is decided purely by ordering. The two properties
 * differ only in the relative order of their class members.
 */
readonly class UnionClassOrderDTO extends DataTransferObject
{
    public string | Enum | SVO $enumFirst;
    public string | SVO | Enum $svoFirst;
}
