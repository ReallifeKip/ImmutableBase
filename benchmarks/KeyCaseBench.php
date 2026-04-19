<?php

declare (strict_types = 1);
namespace Benchmarks;

use Benchmarks\DataTransferObjects\InputKeyDTO;
use Benchmarks\DataTransferObjects\OutputKeyDTO;
use ReallifeKip\ImmutableBase\ImmutableBase;

if (ImmutableBase::state()['cachedMeta'] === []) {
    ImmutableBase::loadCache();
}

/**
 * @Warmup(5)
 * @BeforeMethods("setUp")
 * @Revs(1000)
 * @Iterations(15)
 * @OutputTimeUnit("milliseconds")
 */
class KeyCaseBench
{
    private array $snakePayload;
    private array $camelPayload;
    private OutputKeyDTO $outputKeyDTO;

    public function setUp()
    {
        $this->snakePayload = [
            'first_name'    => 'Kip',
            'last_name'     => 'The Architect',
            'email_address' => 'kip@example.com',
            'account_id'    => 42,
            'is_active'     => true,
            'created_at'    => '2026-01-01T00:00:00Z',
            'updated_at'    => '2026-04-19T00:00:00Z',
        ];

        $this->camelPayload = [
            'firstName'    => 'Kip',
            'lastName'     => 'The Architect',
            'emailAddress' => 'kip@example.com',
            'accountId'    => 42,
            'isActive'     => true,
            'createdAt'    => '2026-01-01T00:00:00Z',
            'updatedAt'    => '2026-04-19T00:00:00Z',
        ];

        $this->outputKeyDTO = OutputKeyDTO::fromArray($this->camelPayload);
    }

    public function benchInputKeyHydration(): void
    {
        InputKeyDTO::fromArray($this->snakePayload);
    }

    public function benchOutputKeySerialization(): void
    {
        $this->outputKeyDTO->toArray(true);
    }

    public function benchRoundTrip(): void
    {
        OutputKeyDTO::fromArray($this->camelPayload)->toArray(true);
    }
}
