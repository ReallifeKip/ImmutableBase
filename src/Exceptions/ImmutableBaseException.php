<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

use Exception;

/**
 * Root exception for all ImmutableBase errors. Provides hierarchical
 * error path tracking via static $depth/$paths counters and prependPath().
 *
 * When nested ImmutableBase constructions fail, each executeSafely() frame appends
 * its property name to $paths. The outermost frame assembles the full
 * chain (e.g. "OrderDTO > $customer > $email > validation failed").
 */
abstract class ImmutableBaseException extends Exception
{
    public static int $depth   = 0;
    public static array $paths = [];
    public ?string $class      = null;
    /**
     * Appends the current property name to the error path stack. When the
     * outermost executeSafely() frame catches the exception ($depth === 0),
     * assembles the full path chain into the exception message and resets
     * the static path accumulator.
     *
     * @param class-string $class The fully-qualified class name at this frame.
     * @param string|null $property The property being processed, or null if not applicable.
     * @return static
     */
    public function prependPath(string $class, ?string $property): static
    {
        if ($property !== null) {
            self::$paths[] = "\$$property";
        }
        if (self::$depth === 0) {
            $pathString = $class;
            if (!empty(self::$paths)) {
                $pathString .= ' > ' . join(' > ', array_reverse(self::$paths));
            }

            $this->message = "$pathString > $this->message";
            self::$paths   = [];
        }

        return $this;
    }
}
