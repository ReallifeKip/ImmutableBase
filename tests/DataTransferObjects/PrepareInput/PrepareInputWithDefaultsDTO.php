<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PrepareInputWithDefaultsDTO extends DataTransferObject
{
    #[Defaults('member')]
    public string $role;
    public string $badge;

    public static function defaultValues(): array
    {
        return ['badge' => 'SILVER'];
    }

    protected static function prepareInput(array $data): array
    {
        return [
            'role'  => strtoupper($data['role']),
            'badge' => $data['badge'] . '_BADGE',
        ];
    }
}
