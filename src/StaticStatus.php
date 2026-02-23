<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase;

use ReallifeKip\ImmutableBase\Types;
use ReflectionClass;

/**
 * Global mutable state container for ImmutableBase's runtime metadata.
 * Holds reflection caches, compiled property resolvers, and configuration
 * flags. All properties are public static for internal access by ImmutableBase.
 *
 * NOT part of the public API. Direct access from userland code may cause
 * cache corruption, inconsistent validation behavior, or silent data loss.
 * Exposed only for testing and cache bootstrapping purposes.
 *
 * @internal
 * @deprecated !!! DO NOT USE !!! Accessing this class may cause unexpected side effects or data corruption.
 * @phpstan-import-type Caches from Types
 */

class StaticStatus
{
    /** @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption. */
    public static bool $debug = false;

    /** @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption. */
    public static ?string $logPath = null;

    /** @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption. */
    public static bool $strict = false;

    /**
     * @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption.
     * @var array<class-string, ReflectionClass>
     */
    public static array $refs = [];

    /**
     * @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption.
     * @var Caches
     */
    public static array $properties = [];
    /**
     * @deprecated !!! DO NOT USE !!! Accessing this property may cause unexpected side effects or data corruption.
     * @var Caches
     */
    public static array $cachedMeta = [];
}
