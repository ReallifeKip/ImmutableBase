<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use Tests\Regressions\Objects\StrictClassAndPropertyCaseDTO;
use Tests\Regressions\Objects\StrictMappedKeyDTO;

/**
 * An input key consumed by a property-level #[InputKeyTo] is not an undeclared
 * key, so strict mode accepts it. Keys nothing maps are still rejected.
 */
class StrictInputKeyTest extends TestCase
{
    public function testKeyMappedByPropertyCaseIsNotReportedAsUndeclared(): void
    {
        $this->assertSame(1, StrictMappedKeyDTO::fromArray(['user-id' => 1])->userId);
    }

    public function testKeyMappedByPropertyCaseIsAcceptedByWith(): void
    {
        $this->assertSame(2, StrictMappedKeyDTO::fromArray(['user-id' => 1])->with(['user_id' => 2])->userId);
    }

    public function testPropertyCaseAlongsideClassCaseUnderStrict(): void
    {
        $dto = StrictClassAndPropertyCaseDTO::fromArray(['firstName' => 'Kip', 'apiKey' => 'k']);
        $this->assertSame(['Kip', 'k'], [$dto->first_name, $dto->API_KEY]);
    }

    public function testUnmappedKeyIsStillRejected(): void
    {
        $this->expectException(StrictViolationException::class);
        StrictMappedKeyDTO::fromArray(['user-id' => 1, 'typo' => 1]);
    }
}
