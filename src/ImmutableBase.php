<?php declare(strict_types=1);

namespace DDD\Shared\Domain;

use Exception;
use ReflectionClass;
use JsonSerializable;
use ReflectionNamedType;
use ReflectionUnionType;

abstract class ImmutableBase implements JsonSerializable
{
    protected ?bool $lock = false;
    /** @var ReflectionClass[] $reflectionsCache */
    private static array $reflectionsCache = [];
    private const HIDDEN = [
        'events',
        'lock',
    ];
    public function __construct($data)
    {
        $this->walkProperties(function ($property) use ($data): void {
            /** @var \ReflectionProperty $property */
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
     * Summary of walkProperties
     * @param callable $callback
     * @return void
     */
    private function walkProperties(callable $callback): void
    {
        foreach (self::getReflection($this)->getProperties() as $property) {
            $property->setAccessible(true);
            $callback($property);
        }
    }
    final public function with(array $data): static
    {
        $this->lock = true;
        $newData = $this->toArray();
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $newData)) {
                $newData[$key] = $value;
            }
        }
        $this->lock = false;
        return new static($newData);
    }
    final public function toArray(): array
    {
        $properties = [];
        $this->walkProperties(function ($property) use (&$properties) {
            /** @var \ReflectionProperty $property */
            $value = $property->getValue($this);
            $key = $property->getName();
            if (in_array($key, self::HIDDEN, true) || $this->lock) {
                return;
            }
            $properties[$key] = $value;
        });
        return $properties;
    }
    final public function jsonSerialize(): array
    {
        return $this->toArray();
    }
    final public function valueDecide(ReflectionNamedType|ReflectionUnionType $type, mixed $value): mixed
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
    final public function builtinTypeValidate(mixed $value, string $type): bool
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