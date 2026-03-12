<?php

namespace Tests\DataTransferObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use Tests\SingleValueObjects\GmailSVO;
use Tests\SingleValueObjects\HotmailSVO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum1;
use Tests\TestObjects\Enum2;
use Tests\TestObjects\Enum;
use Tests\ValueObjects\VO;

readonly class DTO extends DataTransferObject
{
    public string $string;
    public int $int;
    public float $float;
    public bool $bool;
    public array $array;
    public array $emptyArray;
    public string|int|float|bool|array $union;
    public string|int|float|bool $unionWithoutArray;
    public string|int $unionStringAndInt;
    public VO|SVO $unionClasses;
    public GmailSVO|HotmailSVO $unionSVOs;
    public Enum $enum1;
    public Enum $enum2;
    public Enum $enum3;
    public Enum1|Enum2|string $enumMixed;
    public ?string $nullableString;
    public ?int $nullableInt;
    public ?array $nullableArray;
    public ?float $nullableFloat;
    public ?bool $nullableBool;
    public ?Enum $nullableEnum;
    public mixed $mixed;
    #[ArrayOf(DTO::class)]
    /** @property DTO[] $dataTransferObjects */
    public array $dataTransferObjects;
    #[ArrayOf(VO::class)]
    /** @property VO $valueObjects */
    public array $valueObjects;
    #[ArrayOf(SVO::class)]
    /** @property SVO[] $singleValueObjects */
    public array $singleValueObjects;
}
