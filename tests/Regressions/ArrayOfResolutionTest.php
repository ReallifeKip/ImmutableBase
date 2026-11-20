<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\InvalidJsonException;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\InvalidArrayOfItemException;
use Tests\Regressions\Objects\SvoListDTO;
use Tests\Regressions\Objects\TypeErrorFreeDTO;

/**
 * #[ArrayOf] elements resolve through the same matchers as properties
 * (Internal\Resolver), so they reject bad input the same way.
 */
class ArrayOfResolutionTest extends TestCase
{
    public function testMalformedJsonForDtoPropertyIsInvalidJson(): void
    {
        $this->expectException(InvalidJsonException::class);
        SvoListDTO::fromArray(['labels' => [], 'item' => '{"sku":']);
    }

    public function testMalformedJsonForDtoElementIsInvalidJsonLikeTheProperty(): void
    {
        $this->expectException(InvalidJsonException::class);
        TypeErrorFreeDTO::fromArray(['list' => ['{"sku":']]);
    }

    public function testValidJsonForDtoElementStillResolves(): void
    {
        $this->assertSame('a', TypeErrorFreeDTO::fromArray(['list' => ['{"sku":"a"}']])->list[0]->sku);
    }

    public function testObjectForSvoElementIsInvalidArrayOfItem(): void
    {
        $this->expectException(InvalidArrayOfItemException::class);
        SvoListDTO::fromArray(['labels' => ['ok', new \stdClass()]]);
    }

    public function testScalarForSvoElementStillResolves(): void
    {
        $this->assertSame('ok', SvoListDTO::fromArray(['labels' => ['ok']])->labels[0]->value);
    }
}
