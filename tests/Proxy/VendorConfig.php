<?php

declare (strict_types = 1);

namespace Tests\Proxy;

/**
 * Stand-in for a mutable third-party object that IB cannot modify. Its member
 * names deliberately collide with names a proxy might want (get, equals,
 * $class), to show they all reach the held object.
 */
class VendorConfig implements \JsonSerializable
{
    public array $headers = [];
    public string $class  = 'vendor-owned property';
    private ?string $memo = null;

    public function __construct(public int $timeout, public Endpoint $endpoint)
    {}

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function describe(): string
    {
        return "{$this->timeout}s @ {$this->endpoint->url}";
    }

    public function get(string $header): ?string
    {
        return $this->headers[$header] ?? null;
    }

    public function equals(self $other): bool
    {
        return $this->timeout === $other->timeout;
    }

    /** A reader that caches its result on first call. */
    public function memoizedLabel(): string
    {
        return $this->memo ??= strtoupper($this->endpoint->url);
    }

    public function __invoke(int $extra): int
    {
        return $this->timeout + $extra;
    }

    public function __toString(): string
    {
        return $this->describe();
    }

    public function jsonSerialize(): array
    {
        return ['timeout' => $this->timeout, 'url' => $this->endpoint->url, 'headers' => $this->headers];
    }
}
