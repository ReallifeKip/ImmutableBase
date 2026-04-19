<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Exercises every KeyCase variant via property-level OutputKeyTo.
 * Expected toArray(true) output keys (convertCase(propName, KeyCase)):
 *
 *   snakeProp      → snake_prop       (Snake)
 *   camelProp      → camelProp        (Camel — already camelCase)
 *   pascalProp     → PascalProp       (Pascal)
 *   macroProp      → MACRO_PROP       (Macro)
 *   kebabProp      → kebab-prop       (Kebab)
 *   trainProp      → Train-Prop       (Train)
 *   pascalSnakeProp → Pascal_Snake_Prop (PascalSnake)
 *   camelKebabProp  → camel-Kebab-Prop (CamelKebab)
 */
readonly class AllOutputKeyCasesDTO extends DataTransferObject
{
    #[OutputKeyTo(KeyCase::Snake)]
    public string $snakeProp;

    #[OutputKeyTo(KeyCase::Camel)]
    public string $camelProp;

    #[OutputKeyTo(KeyCase::Pascal)]
    public string $pascalProp;

    #[OutputKeyTo(KeyCase::Macro)]
    public string $macroProp;

    #[OutputKeyTo(KeyCase::Kebab)]
    public string $kebabProp;

    #[OutputKeyTo(KeyCase::Train)]
    public string $trainProp;

    #[OutputKeyTo(KeyCase::PascalSnake)]
    public string $pascalSnakeProp;

    #[OutputKeyTo(KeyCase::CamelKebab)]
    public string $camelKebabProp;
}
