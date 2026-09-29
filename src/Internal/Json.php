<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Internal;

use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;

/**
 * JSON helpers shared by construction (fromJson(), nested objects,
 * #[ArrayOf]) and mutation (with()).
 *
 * @internal Not part of the public API; may change without notice.
 */
final class Json
{
    /**
     * Fast check for JSON-like string values: a string whose first
     * non-whitespace character is '{' or '['. Anything else returns false
     * immediately, so scalars and objects cost nothing.
     */
    public static function looksLike(mixed $value): bool
    {
        static $open = ['{' => true, '[' => true];

        return \is_string($value) && isset($open[trim($value)[0] ?? '']);
    }

    /**
     * Decodes a JSON string. When $lenient is true, returns the raw
     * json_decode() result (null on failure) without throwing — for
     * speculative decoding. When false, throws InvalidJsonException on
     * input that is not JSON-like or does not decode.
     *
     * @throws InvalidJsonException
     * @return array<string|int, mixed>|string|int|float|bool|null
     */
    public static function decode(string $data, bool $lenient = true): mixed
    {
        if (!$lenient && !self::looksLike($data)) {
            throw new InvalidJsonException();
        }
        $decoded = json_decode($data, true);
        if (!$lenient && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException();
        }

        return $decoded;
    }
}
