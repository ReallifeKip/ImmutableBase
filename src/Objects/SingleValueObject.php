<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase\Objects;

use Exception;
use ReflectionClass;
use ReallifeKip\ImmutableBase\Objects\ValueObject;
use ReallifeKip\ImmutableBase\Interfaces\HasValidate;

/**
 * @property mixed $value
 */
abstract class SingleValueObject extends ValueObject implements HasValidate
{
    private readonly ReflectionClass $ref;
    private readonly string $type;
    private function __construct($value)
    {
        $this->constructInitialize();
        $this->ref = new ReflectionClass($this);
        $ref = $this->ref;
        $declaringClass = null;
        while ($ref) {
            if ($ref->hasProperty('value')) {
                $prop = $ref->getProperty('value');
                if ($prop->getDeclaringClass()->name === $ref->name) {
                    if (!$prop->isReadOnly()) {
                        throw new Exception(sprintf(
                            'The property "value" in %s must be readonly',
                            $ref->name
                        ));
                    }
                    $declaringClass = $ref;
                    break;
                }
            }
            $ref = $ref->getParentClass();
        }
        if (!$declaringClass) {
            throw new Exception('No readonly "value" property found in inheritance chain');
        }
        $property = $declaringClass->getProperty('value');
        $property->setAccessible(true);
        $property->setValue($this, $value);
        $this->type ??= $property->getType()->getName();

    }
    final public static function from(mixed $value)
    {
        if (!property_exists(static::class, 'value')) {
            throw new Exception('You have to defined the property "value"');
        };
        $instance = new static($value);
        return $instance->__validate($instance->ref);
    }
    final public function equals(mixed $value)
    {
        if (is_object($value) && get_class($value) === static::class) {
            return $this->value === $value->value;
        }
        throw new Exception('equals() expects an instance of '.static::class);
    }
    final public function __toString()
    {
        if (is_string($this->value)) {
            return $this->value;
        }
        throw new Exception('value is not a string, can\'t be convert.');
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
        throw new Exception("Single value object don\'t have $name.");
    }
}
