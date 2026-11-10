<?php

declare (strict_types = 1);

namespace Tests\Proxy;

/** Stand-in for a mutable third-party object that IB cannot modify. */
class VendorConfig implements \JsonSerializable
{
    public array $headers = [];

    public function __construct(public int $timeout, public Endpoint $endpoint)
    {}

    public function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return ['timeout' => $this->timeout, 'url' => $this->endpoint->url, 'headers' => $this->headers];
    }
}
