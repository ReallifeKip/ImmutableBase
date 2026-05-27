<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

readonly class PrepareInputChildDTO extends PrepareInputParentDTO
{
    public string $b;

    protected static function prepareInput(array $data): array
    {
        return ['a' => $data['a'] . '(C)'];
    }
}
