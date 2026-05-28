<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

readonly class PrepareInputGrandChildDTO extends PrepareInputChildDTO
{
    public string $c;
    // 無 prepareInput：確認父層鏈仍正確執行
}
