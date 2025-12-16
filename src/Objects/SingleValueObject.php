<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase\Objects;

use LogicException;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Exceptions\InvalidCompareTargetException;

/** @template TValueType */
abstract class SingleValueObject extends ValueObject
{
    /** @var TValueType */
    protected readonly string|int|float|bool $value;
    private function __construct(string|int|float|bool $value)
    {
        $this->value = $value;
        parent::__construct();

    }
    final public static function from(string|int|float|bool $value)
    {
        return new static($value);
    }
    public function equals(mixed $value)
    {
        if (is_object($value) && get_class($value) === static::class) {
            return $this->value === $value->value;
        }
        throw new InvalidCompareTargetException('equals() expects an instance of '.static::class);
    }
    public function __toString()
    {
        return (string)$this->value;
    }
    public function __invoke()
    {
        return $this->value;
    }
    public function __get(string $name)
    {
        if ($name === 'value') {
            return $this->value;
        }
        throw new LogicException("Single value object only have property 'value'.");
    }
}
