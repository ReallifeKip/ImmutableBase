<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Thrown when a ValueObject's validate() method returns false.
 * Carries the #[Spec] message (if defined) for domain-specific error reporting.
 */
class ValidationChainException extends ValidationException
{
    /**
     * @param string $spec The validation failure message, typically derived
     *                     from the #[Spec] attribute combined with context.
     */
    public function __construct(
        private string $spec
    ) {
        parent::__construct($spec);
    }
    /**
     * Returns the raw Spec value defined on the validation failure class, if any.
     *
     * This value represents the developer-defined semantic intent.
     * The library treats this value as opaque and does not interpret its format
     * or meaning.
     *
     * It may be used by consumers as an error code, mapping key,
     * or human-readable message.
     *
     * Returns null when no Spec attribute is defined.
     * @return ?string
     */
    public function getSpec(): ?string
    {
        return $this->spec;
    }
    /**
     * Replaces the spec value. Used internally by enforceValidationRules()
     * to attach the #[Spec] message after initial construction.
     *
     * @param string $spec The spec string to set.
     * @return self
     */
    public function setSpec(string $spec): self
    {
        $this->spec = $spec;

        return $this;
    }
}
