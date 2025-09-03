<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase;

use Closure;
use Exception;
use ReflectionClass;
use JsonSerializable;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * Data Transfer Object
 *
 * 所有屬性必須為 public readonly
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class DataTransferObject
{
}

/**
 * Value Object
 *
 * 所有屬性必須為 private
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ValueObject
{
}

/**
 * Entity
 *
 * 所有屬性必須為 private
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Entity
{
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class ArrayOf
{
    public bool $error = false;
    public function __construct(
        public string $class = '',
    ) {
        if (trim($this->class) === '') {
            $this->error = true;
        }
    }
}

abstract class ImmutableBase implements JsonSerializable
{
    /** @var ReflectionClass[] $reflectionsCache */
    private static array $reflectionsCache = [];
    private static array $classBoundSetter = [];
    public function __construct(array $data = [])
    {
        $thisClass = (new ReflectionClass($this))->getName();
        $this->walkProperties(function (\ReflectionProperty $property) use ($thisClass, $data) {
            try {
                $key = $property->getName();
                /** @var \ReflectionNamedType|\ReflectionUnionType $type */
                $type = $property->getType();
                $exists = array_key_exists($key, $data);
                $isNull = !isset($data[$key]) ? true : is_null($data[$key]);
                $notExistsOrIsNull = (!$exists || $isNull);
                $nullable = $type->allowsNull();
                $hasDefault = $property->hasDefaultValue();
                $arrayOf = $property->getAttributes(ArrayOf::class);
                $arg = null;
                if ($arrayOf) {
                    if ($arrayOf[0]->newInstance()->error) {
                        throw new Exception('ArrayOf class 不能為空');
                    }
                    $arg = $arrayOf[0]->getArguments()[0];
                    if (!enum_exists($arg) && !is_subclass_of($arg, self::class)) {
                        throw new Exception('ArrayOf 指定的 class 必須為 ImmutableBase 的子類');
                    }
                }
                $value = match(true) {
                    $notExistsOrIsNull && !$nullable => throw new Exception("必須傳入 $type"),
                    $arrayOf && $arg => match(true) {
                        $notExistsOrIsNull && $nullable => null,
                        $notExistsOrIsNull && !$nullable => throw new Exception("必須傳入 array 或 array<{$arg}>"),
                        $exists => is_array($data[$key]) ? array_map(function ($item) use ($arg) {
                            $case = null;
                            if (enum_exists($arg)) {
                                $names = array_column($arg::cases(), 'name');
                                if (in_array($item, $names)) {
                                    $case = $arg::{$item};
                                } elseif (is_int($item) || is_string($item)) {
                                    $case = $arg::tryFrom($item);
                                }
                            }
                            return match(true) {
                                is_array($item) => new $arg($item),
                                $item instanceof $arg => $item,
                                $case !== null => $arg::{$case->name},
                                default => throw new Exception("陣列內容必須是 $arg 或符合其初始化所需之結構")
                            };
                        }, $data[$key]) : throw new Exception("必須傳入 array"),
                    },
                    $notExistsOrIsNull && $nullable && !$hasDefault => null,
                    $notExistsOrIsNull && $nullable && $hasDefault => $property->getDefaultValue(),
                    $exists => $this->valueDecide($type, $data[$key]),
                    default => null
                };
                $declaring = $property->getDeclaringClass()->getName();
                $isOwn = ($declaring === $thisClass);
                if (!$isOwn && $property->isReadOnly()) {
                    if ($property->isInitialized($this)) {
                        return;
                    }
                    $assign = self::$classBoundSetter[$declaring] ??= Closure::bind(
                        function (object $obj, string $prop, mixed $val): void {
                            $obj->$prop = $val;
                        },
                        null,
                        $declaring
                    );
                    $assign($this, $property->getName(), $value);
                } else {
                    $property->setValue($this, $value);
                }
            } catch (Exception $e) {
                if ($msg = $e->getMessage()) {
                    throw new Exception("$key $msg");
                }
            }
        });
    }
    private static function getReflection(object $obj): ReflectionClass
    {
        return self::$reflectionsCache[static::class] ??= new ReflectionClass($obj);
    }
    /**
     * 歷遍屬性
     * @param callable $callback
     * @return void
     */
    private function walkProperties(callable $callback): void
    {
        $ref = self::getReflection($this);
        $dataTransferObject = $ref->getAttributes(DataTransferObject::class);
        $valueObject = $ref->getAttributes(ValueObject::class);
        $entity = $ref->getAttributes(Entity::class);
        if (!$dataTransferObject && !$valueObject && !$entity) {
            throw new Exception('ImmutableBase 子類必須使用 DataTransferObject、ValueObject 或 Entity 任一標註');
        }
        foreach ($ref->getProperties() as $property) {
            if ($entity || $valueObject) {
                if ($property->isPublic()) {
                    throw new Exception('不允許為 public');
                }
            } elseif ($dataTransferObject) {
                if (!$property->isPublic() || !$property->isReadOnly()) {
                    throw new Exception('必須為 public 且 readonly');
                }
            }
            $property->setAccessible(true);
            $callback($property);
        }
    }
    /**
     * 更新並返回新的實例
     * @param array $data
     * @return static
     */
    final public function with(array $data): static
    {
        $newData = [];
        $ref = self::getReflection($this);
        foreach ($ref->getProperties() as $property) {
            try {
                $name = $property->getName();
                $type = $property->getType();
                $value = $property->getValue($this);
                if (isset($data[$name])) {
                    $newData[$name] = $type->isBuiltin() ? $this->valueDecide($type, $data[$name]) : $value->with($data[$name]);
                } else {
                    $newData[$name] = $property->getValue($this);
                }
            } catch (Exception $e) {
                throw new Exception("{$name} {$e->getMessage()}");
            }
        }
        return new static($newData);
    }
    /**
     * 返回屬性數組，支援嵌套物件
     * @return array
     * @throws Exception
     */
    final public function toArray(): array
    {
        $properties = [];
        $this->walkProperties(function (\ReflectionProperty $property) use (&$properties) {
            $value = $property->getValue($this);
            $key = $property->getName();
            if (is_array($value)) {
                $properties[$key] = [];
                foreach ($value as $v) {
                    if (is_object($v) && method_exists($v, 'toArray')) {
                        $properties[$key][] = $v->toArray();
                    } else {
                        $properties[$key][] = $v;
                    }
                }
            } else {
                if ($property->getType()->isBuiltin()) {
                    $properties[$key] = $value;
                } elseif (is_object($value) && method_exists($value, 'toArray')) {
                    $properties[$key] = $value->toArray();
                } elseif ($value) {
                    throw new Exception('不是一種 class 或未提供 toArray 方法');
                }
            }
        });
        return $properties;
    }
    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    private function valueDecide(ReflectionNamedType|ReflectionUnionType $type, mixed $value): mixed
    {
        if ($type instanceof ReflectionUnionType) {
            $names = array_map(fn ($e) => $e->getName(), $type->getTypes());
            if (!in_array('array', $names, true) && is_array($value)) {
                throw new Exception('型別為複合且不包含array，須傳入已實例化的物件。');
            }
            foreach ($type->getTypes() as $t) {
                try {
                    return $this->valueDecide($t, $value);
                } catch (Exception $e) {
                }
            }
            $excepts = implode('|', $names);
            $valueType = (is_object($value) ? get_class($value) : gettype($value));
            throw new Exception("型別錯誤，期望：{$excepts}，傳入：{$valueType}。");
        } else {
            if (!$type->isBuiltin()) {
                $class = $type->getName();
                $value = match(true) {
                    is_array($value) && is_subclass_of($class, self::class) => new $class($value),
                    is_object($value) => $value,
                    default => throw new Exception("型別錯誤，期望：{$class}，傳入：" . (is_object($value) ? get_class($value) : gettype($value)))
                };
            } elseif ($this->builtinTypeValidate($value, $type->getName()) === false) {
                if ($type->allowsNull() && is_null($value)) {
                    return null;
                } else {
                    throw new Exception("型別錯誤，期望：{$type->getName()}，傳入：".(is_object($value) ? get_class($value) : gettype($value)));
                }
            }
        }
        return $value;
    }
    private function builtinTypeValidate(mixed $value, string $type): bool
    {
        return match ($type) {
            'int', 'integer' => is_int($value),
            'float', 'double' => is_float($value),
            'string' => is_string($value),
            'bool', 'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'null' => is_null($value),
            default => false,
        };
    }
}
