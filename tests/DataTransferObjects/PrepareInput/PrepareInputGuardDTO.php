<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PrepareInputGuardDTO extends DataTransferObject
{
    public string $name;
    #[Defaults('guest')]
    public string $role;
    public ?string $extra;

    protected static function prepareInput(array $data): array
    {
        return [
            'name'    => strtoupper($data['name']),
            'role'    => strtoupper($data['role']),
            'phantom' => 'INJECTED',  // 非 property key，應被過濾
        ];
    }
}
