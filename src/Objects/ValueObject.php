<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Objects;

use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\ValidationChainException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\StaticStatus;
use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;

/**
 * @phpstan-import-type Property from Types
 * @phpstan-import-type Caches from Types
 */
abstract readonly class ValueObject extends ImmutableBase
{
    /**
     * ValueObject constructor.
     * Initializes the object with data and triggers the recursive validation chain
     * across the class hierarchy.
     * @param mixed $data Optional initial data to populate the object.
     * @throws ValidationChainException If any validation rule in the lineage fails.
     */
    final protected function __construct(mixed $data)
    {
        self::executeSafely(function () use ($data) {
            if (!$this instanceof SingleValueObject) {
                parent::__construct($data);
                $cache = StaticStatus::$properties;
            } else {
                $this->value = $data;
                $cache       = $this::buildPropertyInheritanceChain($this);
            }
            $class = $cache[static::class];
            $this::enforceValidationRules($this, $class['validateFromSelf'] ? $class['classTree'] : $class['classTreeReversed'], $cache);
        });
    }
    /**
     * Default validation method.
     * Inheriting classes should override this method to provide custom domain logic.
     * @return bool Returns true if validation passes, false otherwise.
     */
    public function validate(): bool
    {
        return true;
    }
    /**
     * Iterates through the class lineage to execute local validation rules.
     * This method scans each class in the provided lineage for a locally defined 'validate' method.
     * If the 'validate' method returns true, the validation is considered successful (no errors).
     * If it returns false, a ValidationChainException is thrown with a detailed error message,
     * potentially augmented by the ErrorMessage attribute if present.
     *
     * @param ReflectionClass[] $lineage An array of ReflectionClass instances representing the class hierarchy.
     * @param Caches $properties
     * @throws ValidationChainException If any validation rule fails (returns false).
     * @return void
     */
    protected static function enforceValidationRules(self $object, array $classTree, array $properties)
    {
        $value = null;
        foreach ($classTree as $class) {
            /** @var Property */
            $ref = $properties[$class] ?? null;
            if ($ref === null || $ref['hasValidate'] === false) {
                continue;
            }
            if ($ref['validateMethod']->invoke($object) === true) {
                continue;
            }
            if ($object instanceof SingleValueObject) {
                if ($value === null) {
                    $value = $object->value;
                }
                $message = "'$value' did not pass validation for {$ref['name']}.";
            } else {
                $message = "Object of class {$ref['name']} is not validated";
            }
            if ($spec = $ref['spec']) {
                $message .= " Reason: $spec";
            }
            throw (new ValidationChainException($message ?? ''))->setSpec($spec ?? '');
        }
    }
}
