<?php

declare (strict_types = 1);

namespace Tests\SingleValueObjects;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

/**
 * @desc basic mail
 */
readonly class MailSVO extends SingleValueObject
{
    /**
     * @desc Hello, you are reading this message
     */
    public string $value;
    public function validate(): bool
    {
        return str_contains($this->value, '@');
    }
}
