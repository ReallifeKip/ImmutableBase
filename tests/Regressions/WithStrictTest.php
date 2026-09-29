<?php

declare (strict_types = 1);

namespace Tests\Regressions;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\ValidationExceptions\StrictViolationException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\DataTransferObjects\LaxDTO;
use Tests\Regressions\Objects\PathItemDTO;
use Tests\Regressions\Objects\StrictItemDTO;

/**
 * with() applies the same strict-mode rule as construction: an undeclared
 * key is rejected under global strict mode or #[Strict], unless #[Lax].
 */
class WithStrictTest extends TestCase
{
    protected function tearDown(): void
    {
        ImmutableBase::strict(false);
    }

    public function testUnknownKeyIsIgnoredByDefault(): void
    {
        $item = PathItemDTO::fromArray(['sku' => 'a']);
        $this->assertSame('b', $item->with(['sku' => 'b', 'typo' => 1])->sku);
    }

    public function testClassLevelStrictRejectsUnknownKey(): void
    {
        try {
            StrictItemDTO::fromArray(['sku' => 'a'])->with(['sku' => 'b', 'typo' => 1]);
            $this->fail('Should have thrown');
        } catch (StrictViolationException $e) {
            // Same message as the constructor's violation: no property segment
            $this->assertSame(StrictItemDTO::class . " > Disallowed 'typo' for " . StrictItemDTO::class . '.', $e->getMessage());
        }
    }

    public function testGlobalStrictRejectsUnknownKey(): void
    {
        $item = PathItemDTO::fromArray(['sku' => 'a']);
        ImmutableBase::strict(true);
        $this->expectException(StrictViolationException::class);
        $item->with(['typo' => 1]);
    }

    public function testLaxOverridesGlobalStrict(): void
    {
        $dto = LaxDTO::fromArray(['string' => 's']);
        ImmutableBase::strict(true);
        $this->assertInstanceOf(LaxDTO::class, $dto->with(['typo' => 1]));
    }
}
