<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown at scan time when an #[InputCaseTo] or #[OutputCaseTo] attribute
 * receives a value that is not a valid KeyCase enum instance.
 */
class InvalidKeyCaseException extends DefinitionException
{
    /**
     * @param mixed  $value     The invalid raw value provided.
     * @param string $attribute The attribute class short name (e.g. 'InputCaseTo').
     * @param string $target    Human-readable location (e.g. 'Foo::class' or 'Foo::$bar').
     */
    public function __construct(mixed $value, string $attribute, string $target)
    {
        parent::__construct("Invalid key case '{$value}' in #[{$attribute}] on {$target}");
    }
}
