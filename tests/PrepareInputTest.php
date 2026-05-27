<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReallifeKip\ImmutableBase\Exceptions\InitializationExceptions\RequiredValueException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use Tests\DataTransferObjects\PrepareInput\PrepareInputBasicDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputChildDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputGrandChildDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputGuardDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputParentDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputRequiredDTO;
use Tests\DataTransferObjects\PrepareInput\PrepareInputWithDefaultsDTO;

class PrepareInputTest extends TestCase
{
    protected function setUp(): void
    {
        $s               = &ImmutableBase::state();
        $s['cachedMeta'] = [];
        $s['properties'] = [];
        $s['refs']       = [];
    }

    // ── 1. 基本轉換 ──────────────────────────────────────────

    public function testTransformsExistingInputKey(): void
    {
        $dto = PrepareInputBasicDTO::fromArray([
            'email' => '  BILL@EXAMPLE.COM  ',
            'name'  => 'Bill',
        ]);

        $this->assertSame('bill@example.com', $dto->email);
        $this->assertSame('Bill', $dto->name);
    }

    public function testFromJsonAlsoTriggersPrepareInput(): void
    {
        $dto = PrepareInputBasicDTO::fromJson(json_encode([
            'email' => '  BILL@EXAMPLE.COM  ',
            'name'  => 'Bill',
        ]));

        $this->assertSame('bill@example.com', $dto->email);
    }

    // ── 2. 看見預設值 ─────────────────────────────────────────

    public function testSeesDefaultsAttributeValue(): void
    {
        $dto = PrepareInputWithDefaultsDTO::fromArray([]);

        $this->assertSame('MEMBER', $dto->role);   // #[Defaults('member')] → strtoupper
    }

    public function testSeesDefaultValuesMethodValue(): void
    {
        $dto = PrepareInputWithDefaultsDTO::fromArray([]);

        $this->assertSame('SILVER_BADGE', $dto->badge);  // defaultValues() → append _BADGE
    }

    public function testInputWinsOverDefault(): void
    {
        $dto = PrepareInputWithDefaultsDTO::fromArray(['role' => 'admin']);

        $this->assertSame('ADMIN', $dto->role);  // input 'admin' → strtoupper
    }

    // ── 3. 繼承鏈疊加 ─────────────────────────────────────────

    public function testParentPrepareInputRunsAlone(): void
    {
        $dto = PrepareInputParentDTO::fromArray(['a' => 'hello']);

        $this->assertSame('[P:hello]', $dto->a);
    }

    public function testChainRunsParentThenChild(): void
    {
        $dto = PrepareInputChildDTO::fromArray(['a' => 'hello', 'b' => 'world']);

        $this->assertSame('[P:hello](C)', $dto->a);  // 父先包，子再追加
        $this->assertSame('world', $dto->b);
    }

    public function testGrandChildWithoutOverrideInheritsFullChain(): void
    {
        $dto = PrepareInputGrandChildDTO::fromArray([
            'a' => 'hello',
            'b' => 'world',
            'c' => 'foo',
        ]);

        $this->assertSame('[P:hello](C)', $dto->a);  // 父 + 子鏈，孫無 override
        $this->assertSame('foo', $dto->c);
    }

    // ── 4. array_intersect_key 保護 ───────────────────────────

    public function testPhantomKeyIsFilteredOut(): void
    {
        $dto = PrepareInputGuardDTO::fromArray(['name' => 'alice']);

        $this->assertSame('ALICE', $dto->name);
        $this->assertSame('GUEST', $dto->role);   // #[Defaults] 可見且可轉換
        $this->assertNull($dto->extra);
        // 'phantom' 不是 property，不存在於物件上
        $this->assertFalse(property_exists($dto, 'phantom'));
    }

    public function testDefaultedKeyCanBeTransformed(): void
    {
        $dto = PrepareInputGuardDTO::fromArray(['name' => 'bob', 'role' => 'editor']);

        $this->assertSame('BOB', $dto->name);
        $this->assertSame('EDITOR', $dto->role);  // input 覆蓋 default，再被 strtoupper
    }

    // ── 5. 型別安全 ───────────────────────────────────────────

    public function testRequiredWithNoSourceStillThrows(): void
    {
        $this->expectException(RequiredValueException::class);

        PrepareInputRequiredDTO::fromArray([]);
    }

    // ── 6. with() 不執行 prepareInput ─────────────────────────

    public function testWithDoesNotRunPrepareInput(): void
    {
        $original = PrepareInputBasicDTO::fromArray([
            'email' => '  BILL@EXAMPLE.COM  ',
            'name'  => 'Bill',
        ]);
        $this->assertSame('bill@example.com', $original->email);

        $updated = $original->with(['name' => 'Bob']);

        // email 已是 normalized，with() 不重跑 prepareInput，值不變
        $this->assertSame('bill@example.com', $updated->email);
        $this->assertSame('Bob', $updated->name);
    }

    // ── 7. Cache 相容性 ───────────────────────────────────────

    public function testOldCacheWithoutHasPrepareInputFallsBackGracefully(): void
    {
        // 先暖機：建立真實的 property cache
        PrepareInputBasicDTO::fromArray(['email' => 'warmup@example.com', 'name' => 'Warmup']);

        $s = &ImmutableBase::state();

        // 模擬舊 cache：從已建立的 $s['properties'] 中移除 hasPrepareInput 欄位
        // （代表磁碟 cache 在加入此欄位前產生的舊格式）
        unset($s['properties'][PrepareInputBasicDTO::class]['hasPrepareInput']);

        // 不應拋出，prepareInput 安全跳過（?? false fallback）
        $dto = PrepareInputBasicDTO::fromArray([
            'email' => 'test@example.com',
            'name'  => 'Test',
        ]);

        // prepareInput 未執行，email 保持原值（無 trim/lowercase）
        $this->assertSame('test@example.com', $dto->email);
    }
}
