<?php
namespace Tests\Attacks\Objects\DeepNesting;

use ReallifeKip\ImmutableBase\Objects\SingleValueObject;

readonly class EmailSVO extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return str_contains($this->value, '@');
    }
}
