<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Objects;

use JsonSerializable;
use ReallifeKip\ImmutableBase\Interfaces\SingleValueObject as InterfacesSingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

/**
 * Base class for single-value domain objects that wrap a scalar value
 * with optional validation via the inherited validate() method.
 *
 * Provides scalar-like ergonomics: string casting, invocation, and
 * JSON serialization all delegate to the wrapped $value property.
 *
 * Construction is exclusively through the static from() factory method.
 *
 * ISSUE: https://github.com/bmewburn/vscode-intelephense/issues/3528
 * Remove once the Intelephense bug is fixed upstream
 * @property string $value
 */
abstract readonly class SingleValueObject extends ValueObject implements InterfacesSingleValueObject, JsonSerializable
{
    /**
     * Named constructor to instantiate the object.
     * @param mixed $value The value to wrap.
     * @return static A new instance of the calling class.
     */
    final public static function from(mixed $value): static
    {
        return new static($value);
    }
    /**
     * Returns the string representation of the internal value.
     * @return string
     */
    final public function __toString(): string
    {
        return (string) $this->value;
    }
    /**
     * Allows the object to be called as a function, returning its internal value.
     * @return mixed The internal 'value' property.
     */
    final public function __invoke()
    {
        return $this->value;
    }
    /**
     * Serializes the wrapped value for json_encode(). Returns the raw
     * scalar rather than an object structure, ensuring SVOs produce
     * clean JSON output (e.g. "alice@example.com" instead of {"value":"alice@example.com"}).
     *
     * @return mixed
     */
    final public function jsonSerialize(): mixed
    {
        return $this->value;
    }
    /**
     * SVOs intentionally disable property-level default filling.
     *
     * Unlike DTO/VO, a SingleValueObject conceptually has a single
     * required payload (`$value`) supplied via `from()`. Returning
     * defaults here could hide missing/invalid input and weaken the
     * semantic contract of the value object.
     *
     * This override remains only to block accidental usage inherited
     * from ImmutableBase.
     *
     * @deprecated SingleValueObject does not support defaultValues(); always pass an explicit value via from().
     * @return array
     */
    final public static function defaultValues(): array
    {
        return [];
    }
}
