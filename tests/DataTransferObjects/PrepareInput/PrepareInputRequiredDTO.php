<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\PrepareInput;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class PrepareInputRequiredDTO extends DataTransferObject
{
    public string $required;  // non-nullable，無 default

    protected static function prepareInput(array $data): array
    {
        // 'required' 不在 $data（無 input 且無 default），array_intersect_key 過濾
        return ['required' => 'should-not-appear'];
    }
}
