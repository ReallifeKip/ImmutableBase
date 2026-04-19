<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Exercises every KeyCase variant whose format is expressible as a valid PHP
 * property name (Kebab/Train/CamelKebab use hyphens and are not valid PHP
 * identifiers, so they cannot appear as property-level InputKeyTo targets).
 *
 * Each property name IS in the declared case, so any input format that shares
 * the same word boundaries will be accepted:
 *
 *   $snake_input      (Snake)       ← accepts: snakeInput, Snake-Input, SNAKE_INPUT …
 *   $camelInput       (Camel)       ← accepts: snake_input, Snake-Input, SNAKE_INPUT …
 *   $PascalInput      (Pascal)      ← accepts: pascal_input, camelInput, PASCAL_INPUT …
 *   $MACRO_INPUT      (Macro)       ← accepts: macroInput, macro_input, Macro-Input …
 *   $Pascal_Snake_Input (PascalSnake) ← accepts: pascalSnakeInput, pascal-snake-input …
 */
readonly class AllInputKeyCasesDTO extends DataTransferObject
{
    #[InputKeyTo(KeyCase::Snake)]
    public string $snake_input;

    #[InputKeyTo(KeyCase::Camel)]
    public string $camelInput;

    #[InputKeyTo(KeyCase::Pascal)]
    public string $PascalInput;

    #[InputKeyTo(KeyCase::Macro)]
    public string $MACRO_INPUT;

    #[InputKeyTo(KeyCase::PascalSnake)]
    public string $Pascal_Snake_Input;
}
