<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects;

final class Money implements \JsonSerializable
{
    public function __construct(private int $cents)
    {}

    public function jsonSerialize(): string
    {
        return \sprintf('%d.%02d', intdiv($this->cents, 100), $this->cents % 100);
    }
}
