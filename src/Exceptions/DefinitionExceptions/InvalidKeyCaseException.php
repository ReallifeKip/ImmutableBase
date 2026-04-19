<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

/**
 * Thrown at scan time when an #[InputKeyTo] or #[OutputKeyTo] attribute
 * receives a value that is not a valid KeyCase enum instance.
 */
class InvalidKeyCaseException extends \InvalidArgumentException
{
    /**
     * @param mixed  $value     The invalid raw value provided.
     * @param string $attribute The attribute class short name (e.g. 'InputKeyTo').
     * @param string $target    Human-readable location (e.g. 'Foo::class' or 'Foo::$bar').
     */
    public function __construct(mixed $value, string $attribute, string $target)
    {
        parent::__construct("Invalid key case '{$value}' in #[{$attribute}] on {$target}");
    }
}
