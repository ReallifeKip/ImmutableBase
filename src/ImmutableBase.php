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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Relaxed
{
}
/**
 * 暴露
 *
 * 用以標記為可被 toArray() 輸出的屬性
*/
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Expose
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
            $key = $property->getName();
            /** @var \ReflectionNamedType|\ReflectionUnionType $type */
            $type = $property->getType();
            $exists = array_key_exists($key, $data);
            $nullable = $type->allowsNull();
            $hasDefault = $property->hasDefaultValue();
            try {
                $value = match(true) {
                    !$exists && !$nullable => throw new Exception("必須傳入 $type"),
                    !$exists && $nullable && !$hasDefault => null,
                    !$exists && $nullable && $hasDefault => $property->getDefaultValue(),
                    $exists => $this->valueDecide($type, $data[$key]),
                    default => false
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
        $relaxed = $this->isRelaxed($ref);
        foreach ($ref->getProperties() as $property) {
            $reason = $property->getAttributes(Reason::class);
            if ($property->isPublic() && !$property->isReadOnly()) {
                throw new Exception("{$property->getName()}：不允許 public 屬性非 readonly");
            }
            if (!$relaxed && !$property->isPrivate()) {
                if (empty($reason)) {
                    throw new Exception("{$property->getName()}：非 private，需透過 #[Reason('原因')] 說明設計原因，或將 class 標記為 #[Relaxed]");
                } elseif ($reason[0]->newInstance()->error) {
                    throw new Exception("{$property->getName()}：public readonly 或 protected 設計原因不得為空");
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
            $name = $property->getName();
            $type = $property->getType();
            $value = $property->getValue($this);
            if (isset($data[$name])) {
                $newData[$name] = $type->isBuiltin() ? $this->valueDecide($type, $data[$name]) : $value->with($data[$name]);
            } else {
                $newData[$name] = $property->getValue($this);
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
            if ($property->getAttributes(Expose::class)) {
                if ($property->getType()->isBuiltin()) {
                    $properties[$key] = $value;
                } elseif (is_object($value) && method_exists($value, 'toArray')) {
                    $properties[$key] = $value->toArray();
                } elseif ($value) {
                    throw new Exception("$key 不是一種 class 或未提供 toArray 方法");
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
                throw new Exception("型別為複合且不包含array，須傳入已實例化的物件。");
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
                    is_object($value) && $value::class === $class => $value,
                    default => throw new Exception()
                };
            } elseif ($this->builtinTypeValidate($value, $type->getName()) === false) {
                throw new Exception();
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
    private function isRelaxed(ReflectionClass $class): bool
    {
        while ($class) {
            if (!empty($class->getAttributes(Relaxed::class))) {
                return true;
            }
            $class = $class->getParentClass();
        }
        return false;
    }
}
