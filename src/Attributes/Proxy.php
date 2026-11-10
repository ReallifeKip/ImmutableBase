<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Attributes;

/**
 * Names the class an ImmutableProxy holds, for ImmutableBase at runtime. On
 * a property typed as the ImmutableProxy base class (pair it with a doc
 * comment for the IDE, which does not read attributes):
 *
 *     /** @var ImmutableProxy&VendorConfig *\/
 *     #[Proxy(VendorConfig::class)]
 *     public ImmutableProxy $config;
 *
 * or on a typed subclass, shared by every property typed with it:
 *
 *     #[Proxy(VendorConfig::class)]
 *     final readonly class VendorConfigProxy extends ImmutableProxy {}
 *
 * When both the subclass and the property carry one, they must agree.
 *
 * @example #[Proxy(\Symfony\Component\HttpFoundation\Request::class)]
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class Proxy
{
    /**
     * @param class-string $class The class (or interface) of the held object.
     */
    private function __construct(string $class)
    {
        // Read through reflection only; prevents manual instantiation.
    }
}
