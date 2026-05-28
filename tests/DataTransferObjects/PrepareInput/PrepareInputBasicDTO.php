<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PrepareInputBasicDTO extends DataTransferObject
{
    public string $email;
    public string $name;

    protected static function prepareInput(array $data): array
    {
        return ['email' => strtolower(trim($data['email']))];
    }
}
