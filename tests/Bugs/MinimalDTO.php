<?php

namespace Tests\Bugs;

use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

readonly class MinimalDTO extends DataTransferObject
{
    public StatusEnum $status;

    public static function defaultValues(): array
    {
        return [
            'status' => StatusEnum::Active,
        ];
    }
}
