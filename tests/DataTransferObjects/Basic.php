<?php

namespace Tests\DataTransferObjects;

use Tests\Enum;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\DataTransferObject;

#[DataTransferObject]
class Basic extends ImmutableBase
{
    public readonly string  $string;
    public readonly int     $int;
    public readonly array   $array;
    public readonly object  $object;
    public readonly float   $float;
    public readonly bool    $bool;
    public readonly Enum    $enum;
    public readonly ?string  $nullable_str;
    public readonly ?int     $nullable_int;
    public readonly ?array   $nullable_array;
    public readonly ?object  $nullable_object;
    public readonly ?float   $nullable_float;
    public readonly ?bool    $nullable_bool;
    public readonly ?Enum    $nullable_enum;
}
