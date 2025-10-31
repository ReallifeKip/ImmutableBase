<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase\Objects;

use Exception;
use ReflectionClass;
use ReallifeKip\ImmutableBase\ImmutableBase;

abstract class ValueObject extends ImmutableBase
{
    public function validate(): bool
    {
        return true;
    }
    final protected function __validate(ReflectionClass $ref)
    {
        do {
            if ($ref->hasMethod('validate')) {
                $method = $ref->getMethod('validate');
                if ($method->getDeclaringClass()->getName() === $ref->getName()) {
                    if ($method->invoke($this) === false) {
                        $value = (new ReflectionClass($this))->getProperty('value')->getValue($this);
                        throw new Exception(static::class." $value is not validated for ".$ref->name);
                    };
                }
            }
            $ref = $ref->getParentClass();
        } while ($ref);
        return $this;
    }
}
