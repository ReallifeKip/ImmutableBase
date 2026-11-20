<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\OutputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Exercises every KeyCase variant via property-level OutputCaseTo.
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
    #[OutputCaseTo(KeyCase::Snake)]
    public string $snakeProp;

    #[OutputCaseTo(KeyCase::Camel)]
    public string $camelProp;

    #[OutputCaseTo(KeyCase::Pascal)]
    public string $pascalProp;

    #[OutputCaseTo(KeyCase::Macro)]
    public string $macroProp;

    #[OutputCaseTo(KeyCase::Kebab)]
    public string $kebabProp;

    #[OutputCaseTo(KeyCase::Train)]
    public string $trainProp;

    #[OutputCaseTo(KeyCase::PascalSnake)]
    public string $pascalSnakeProp;

    #[OutputCaseTo(KeyCase::CamelKebab)]
    public string $camelKebabProp;
}
