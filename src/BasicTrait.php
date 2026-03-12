<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use ReflectionClass;
use ReflectionProperty;

trait BasicTrait
{
    /**
     * Reads constructor arguments from a reflected PHP attribute.
     *
     * Used as a lightweight helper during metadata scanning to avoid
     * repetitive boilerplate around `getAttributes()` + `getArguments()`.
     *
     * When `$getFirst` is true (default), returns only the first argument
     * of the first matching attribute instance. When false, returns the
     * full argument array of the first matching instance.
     *
     * If the attribute is not present, or present without constructor
     * arguments, `null` is returned.
     *
     * @param ReflectionClass|ReflectionProperty $target Reflection target to inspect.
     * @param class-string $name Fully-qualified attribute class name.
     * @param bool $getFirst Whether to return only the first argument.
     * @return mixed
     */
    public static function getAttributeArgument(ReflectionClass | ReflectionProperty $target, string $name, bool $getFirst = true): mixed
    {
        if ($value = $target->getAttributes($name)) {
            $value = $value[0];
            if ($value = $value->getArguments()) {
                return $getFirst ? $value[0] : $value;
            }

            return null;
        }

        return null;
    }
}
