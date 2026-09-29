<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions;

use Exception;

/**
 * Root exception for all ImmutableBase errors. Provides hierarchical
 * error path tracking via prependPath().
 *
 * When nested ImmutableBase constructions fail, each executeSafely() frame
 * the exception passes through prepends its property name and rewrites the
 * message. The outermost frame writes last, so the final message carries the
 * full chain (e.g. "OrderDTO > $customer > $email > validation failed").
 *
 * The path lives on the exception instance itself: an exception that is
 * swallowed (e.g. by union member fallback) or a foreign exception escaping
 * mid-construction can no longer leave stale state behind for later errors.
 */
abstract class ImmutableBaseException extends Exception
{
    /**
     * @deprecated No longer read or written; error paths are tracked per exception instance.
     */
    public static int $depth = 0;
    /**
     * @deprecated No longer read or written; error paths are tracked per exception instance.
     */
    public static array $paths = [];
    public ?string $class      = null;
    /** @var list<string> Property segments collected so far, outermost first. */
    private array $pathSegments = [];
    /** The message as originally thrown, before any path was prepended. */
    private ?string $originalMessage = null;
    /**
     * Prepends the current property name to this exception's error path and
     * rewrites the message as "$class > $path... > original message".
     *
     * Every frame rewrites the message; the outermost frame writes last, so
     * its class name is the one that remains.
     *
     * @param class-string $class The fully-qualified class name at this frame.
     * @param string|null $property The property being processed, or null if not applicable.
     * @return static
     */
    public function prependPath(string $class, ?string $property): static
    {
        $this->originalMessage ??= $this->message;
        if ($property !== null) {
            array_unshift($this->pathSegments, "\$$property");
        }
        $this->message = implode(' > ', [$class, ...$this->pathSegments, $this->originalMessage]);

        return $this;
    }
}
