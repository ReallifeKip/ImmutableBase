<?php

declare(strict_types=1);

namespace ReallifeKip\ImmutableBase;

use Exception;
use ReflectionClass;
use JsonSerializable;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * 鬆散模式
 *
 * 此模式下的 class 不強制要求填寫 Reason
 * @deprecated v2.2.0
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Relaxed
{
}
/**
 * 暴露
 *
 * 用以標記為可被 toArray() 輸出的屬性
 * @deprecated v2.2.0
*/
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Expose
{
}

/**
 * Data Transfer Object
 *
 * 所有屬性必須為 public readonly
 */
final class DataTransferObject
{
}

/**
 * Value Object
 *
 * 所有屬性必須為 private
 */
final class ValueObject
{
}

/**
 * Entity
 *
 * 所有屬性必須為 private
 */
final class Entity
{
}

/**
 * 設計原因
 *
 * 屬性非 private 時強制使用此標註
 *
 * 用以說明屬性設為 protected 原因
 * @param string $why 設計原因
 * @throws Exception 當設計原因為空時拋出異常
*/
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Reason
{
    public bool $error = false;
    public function __construct(
        public string $why = '',
    ) {
        if (trim($why) === '') {
            $this->error = true;
        }
    }
}

abstract class ImmutableBase implements JsonSerializable
{
    /** @var ReflectionClass[] $reflectionsCache */
    private static array $reflectionsCache = [];
    public function __construct(array $data = [])
    {
        $this->walkProperties(function (\ReflectionProperty $property) use ($data): void {
            try {
                $key = $property->getName();
                /** @var \ReflectionNamedType|\ReflectionUnionType $type */
                $type = $property->getType();
                $exists = array_key_exists($key, $data);
                $nullable = $type->allowsNull();
                $hasDefault = $property->hasDefaultValue();
                $value = match(true) {
                    !$exists && !$nullable => throw new Exception("$key 必須傳入 $type"),
                    !$exists && $nullable && !$hasDefault => null,
                    !$exists && $nullable && $hasDefault => $property->getDefaultValue(),
                    $exists => $this->valueDecide($type, $data[$key]),
                    default => null
                };
                $property->setValue($this, $value);
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
        foreach ($ref->getProperties() as $property) {
            $name = $property->getName();
            if ($entity || $valueObject) {
                if (!$property->isPrivate()) {
                    throw new Exception("{$name}：必須為 private");
                }
            } elseif ($dataTransferObject) {
                if (!$property->isPublic() || !$property->isReadOnly()) {
                    throw new Exception("{$name}：必須為 public 且 readonly");
                }
            }
            $property->setAccessible(true);
            $callback($property);
        }
    }
    /**
     * 更新並返回新的實例
     * @param array $data
     * @return ImmutableBase
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
            if ($property->getType()->isBuiltin()) {
                $properties[$key] = $value;
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $properties[$key] = $value->toArray();
            } elseif ($value) {
                throw new Exception("$key 不是一種 class 或未提供 toArray 方法");
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
                throw new Exception("型別為複合且不包含array，須傳入已實例化的物件。");
            }
            foreach ($type->getTypes() as $t) {
                return $this->valueDecide($t, $value);
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
