<?php

declare (strict_types = 1);

namespace Tests\ValueObjects;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use Tests\DataTransferObjects\DTO1;
use Tests\DataTransferObjects\DTO2;
use Tests\DataTransferObjects\DTO;
use Tests\SingleValueObjects\SVO;
use Tests\TestObjects\Enum1;
use Tests\TestObjects\Enum2;
use Tests\TestObjects\Enum;

#[Spec('Validate failed.')]
readonly class VO extends ValueObject
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
    public DTO1|DTO2|VO|SVO $unionClasses;
    public Enum $enum1;
    public Enum $enum2;
    public Enum $enum3;
    public ?string $nullableString;
    public ?int $nullableInt;
    public ?array $nullableArray;
    public ?float $nullableFloat;
    public ?bool $nullableBool;
    public ?Enum $nullableEnum;
    public Enum1|Enum2|string $enumMixed;
    public mixed $mixed;
    #[ArrayOf(DTO::class)]
    /** @property DTO|[] $dataTransferObjects */
    public array $dataTransferObjects;
    #[ArrayOf(VO::class)]
    /** @property VO $valueObjects */
    public array $valueObjects;
    #[ArrayOf(SVO::class)]
    /** @property SVO[] $singleValueObjects */
    public array $singleValueObjects;
    public function validate(): bool
    {
        return in_array($this->string, ['string', 'string2'], true);
    }
}
