<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

/**
 * Thrown at scan time when an #[InputKey] or #[OutputKey] attribute does not
 * receive a non-empty string, or when two properties of one class declare
 * the same #[InputKey].
 */
class InvalidKeyNameException extends \InvalidArgumentException
{
    /**
     * @param mixed       $value     The raw value provided.
     * @param string      $attribute The attribute class short name (e.g. 'InputKey').
     * @param string      $target    Human-readable location (e.g. 'Foo::$bar').
     * @param string|null $usedBy    The property that already declares the same key, if that is the problem.
     */
    public function __construct(mixed $value, string $attribute, string $target, ?string $usedBy = null)
    {
        $shown = \is_string($value) ? "'{$value}'" : get_debug_type($value);
        parent::__construct($usedBy === null
            ? "Invalid key {$shown} in #[{$attribute}] on {$target}: expected a non-empty string"
            : "Duplicate key {$shown} in #[{$attribute}] on {$target}: already declared on {$usedBy}");
    }
}
