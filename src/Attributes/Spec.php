<?php

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Attaches a domain-specific validation message to a ValueObject or
 * SingleValueObject class. When validate() returns false, this message
 * is included in the thrown ValidationChainException.
 *
 * The value is treated as opaque by the library — consumers may use it
 * as an error code, i18n key, or human-readable description.
 *
 * @example #[Spec('Email must contain exactly one @ symbol')]
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
abstract class Spec
{
}
