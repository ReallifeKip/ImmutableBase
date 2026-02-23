<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions;

use ReallifeKip\ImmutableBase\Exceptions\ValidationException;

/**
 * Reserved for future attribute-based validation rule failures.
 * Currently unused — exists as an extension point for custom
 * validation attributes beyond validate().
 */
class AttributeValidationException extends ValidationException
{
}
