<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions\InvalidSerializeTargetException;
use Tests\Regressions\Objects\EqualsDTO;
use Tests\Regressions\Objects\MixedDTO;
use Tests\Regressions\Objects\Money;

/**
 * A foreign object reachable through `mixed` or a plain `array` must not
 * crash toArray() with "Call to undefined method X::toArray()".
 */
class SerializeForeignObjectTest extends TestCase
{
    public function testJsonSerializableIsSerializedThroughJsonSerialize(): void
    {
        $dto = MixedDTO::fromArray(['any' => new Money(1250)]);
        $this->assertSame(['any' => '12.50'], $dto->toArray());
        $this->assertSame('{"any":"12.50"}', $dto->toJson());
    }

    public function testJsonSerializableInsidePlainArray(): void
    {
        $dto = EqualsDTO::fromArray(['raw' => [new Money(5)], 'levels' => []]);
        $this->assertSame(['0.05'], $dto->toArray()['raw']);
    }

    public function testOtherForeignObjectInMixedThrows(): void
    {
        $this->expectException(InvalidSerializeTargetException::class);
        MixedDTO::fromArray(['any' => new \DateTimeImmutable()])->toArray();
    }

    public function testOtherForeignObjectInPlainArrayThrows(): void
    {
        $this->expectException(InvalidSerializeTargetException::class);
        EqualsDTO::fromArray(['raw' => [new \stdClass()], 'levels' => []])->toJson();
    }
}
