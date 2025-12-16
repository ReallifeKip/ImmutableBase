<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase\Objects;

use ReflectionClass;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Interfaces\HasValidation;
use ReallifeKip\ImmutableBase\Exceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\Exceptions\InvalidCompareTargetException;

abstract class ValueObject extends ImmutableBase implements HasValidation
{
    public static bool $validateFromParent = true;
    public static string $validateErrorMessage = '';
    /** @var array<string, ReflectionClass> */
    private static array $classes = [];
    private static array $validationChainCache = [];
    public function __construct(mixed $data = null)
    {
        if (is_array($data)) {
            parent::__construct($data);
        }
        $this->compileMetadata();
        $lineage = self::$validationChainCache[static::class];
        if (static::$validateFromParent === false) {
            $lineage = array_reverse($lineage);
        }
        $this->enforceValidationRules($lineage);
    }
    protected function compileMetadata()
    {
        if (!isset(self::$classes[static::class])) {
            self::$validationChainCache[static::class] = [];
            for ($c = new ReflectionClass($this); $c; $c = $c->getParentClass()) {
                self::$classes[$c->getName()] ??= $c;
                array_unshift(self::$validationChainCache[static::class], $c);
            }
        }
    }
    public function equals(mixed $value)
    {
        if (is_object($value) && get_class($value) === static::class) {
            $result = true;
            $ref = new ReflectionClass($this);
            $target = new ReflectionClass($value);
            foreach ($ref->getProperties() as $property) {
                $propertyValue = $property->getValue($this);
                $targetValue = $target->getProperty($property->name)->getValue($value);
                if ($propertyValue instanceof ValueObject) {
                    $result = $propertyValue->equals($targetValue);
                } elseif ($propertyValue !== $targetValue) {
                    $result = false;
                }
            }
            return $result;
        }
        throw new InvalidCompareTargetException('equals() expects an instance of '.static::class);
    }
    private function enforceValidationRules(array $lineage)
    {
        foreach ($lineage as $ref) {
            if ($ref->hasMethod('validate') === false) {
                continue;
            }
            if (($method = $ref->getMethod('validate'))->getDeclaringClass()->name !== $ref->name) {
                continue;
            }
            if ($method->isAbstract()) {
                continue;
            }
            if ($method->invoke($this) === true) {
                continue;
            }
            if (is_subclass_of($this, SingleValueObject::class) || $this instanceof SingleValueObject) {
                $value = $ref->getProperty('value')->getValue($this);
                $message = "'$value' did not pass validation for {$ref->name}.";
                $validateErrorMessage = $ref->getProperty('validateErrorMessage')->getValue();
                if (trim($validateErrorMessage)) {
                    $message .= " Reason: $validateErrorMessage";
                }
            } else {
                $message = "Object of class ".$ref->name." is not validated";
            }
            throw new ValidationChainException($message);
        }
    }
}
