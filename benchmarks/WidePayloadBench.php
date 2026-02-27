<?php

declare (strict_types = 1);
namespace Benchmarks;

use Benchmarks\DataTransferObjects\TargetDTO;
use Benchmarks\DataTransferObjects\WideFlatDTO;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\StaticStatus;

if (StaticStatus::$cachedMeta === []) {
    ImmutableBase::loadCache();
}

/**
 * @Warmup(5)
 * @BeforeMethods("setUp")
 * @Revs(1000)
 * @Iterations(15)
 * @OutputTimeUnit("milliseconds")
 */
class WidePayloadBench
{
    private array $noisePayload;
    private array $widePayload;

    public function setUp()
    {
        $this->noisePayload = [];
        for ($i = 0; $i < 2000; $i++) {
            $key                      = 'unused_field_' . $i;
            $this->noisePayload[$key] = "rubbish_data_{$i}";
        }
        $this->noisePayload['id']       = 101;
        $this->noisePayload['name']     = 'Kip';
        $this->noisePayload['email']    = 'kip@bench.mark';
        $this->noisePayload['isActive'] = true;
        $this->noisePayload['score']    = 99.9;

        $this->widePayload = [];
        for ($i = 0; $i < 100; $i++) {
            $this->widePayload["col_{$i}"] = "value_{$i}";
        }
    }
    public function benchNoiseFiltering(): void
    {
        TargetDTO::fromArray($this->noisePayload);
    }
    public function benchWideFlattened(): void
    {
        WideFlatDTO::fromArray($this->widePayload);
    }
}
