<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PrepareInputParentDTO extends DataTransferObject
{
    public string $a;

    protected static function prepareInput(array $data): array
    {
        return ['a' => '[P:' . $data['a'] . ']'];
    }
}
