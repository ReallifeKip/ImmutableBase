<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputKeyTo;
use ReallifeKip\ImmutableBase\Attributes\OutputKeyTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Mirrors the HTTP-header pattern from index.php (corrected).
 *
 * Input source: browser request headers (kebab-case):
 *   cache-control, sec-ch-ua, sec-ch-ua-mobile, sec-ch-ua-platform
 *
 * Class-level InputKeyTo(Snake): converts all input keys to snake_case.
 *   Works for snake_case property names (cache_control, sec_ch_ua, sec_ch_ua_platform).
 *
 * $SEC_CH_UA_MOBILE is MACRO_CASE — class Snake produces 'sec_ch_ua_mobile' which
 * does NOT match. Property-level InputKeyTo(Macro) fixes this:
 *   'sec-ch-ua-mobile' → convertCase → 'SEC_CH_UA_MOBILE' ✓
 */
#[InputKeyTo(KeyCase::Snake)]
#[OutputKeyTo(KeyCase::PascalSnake)]
readonly class HeadersMimicDTO extends DataTransferObject
{
    /** cache-control → Snake → cache_control ✓ (class-level handles it) */
    public string $cache_control;

    /** sec-ch-ua → Snake → sec_ch_ua ✓ (class-level handles it) */
    public string $sec_ch_ua;

    /**
     * MACRO_CASE property: class Snake would give sec_ch_ua_mobile ≠ SEC_CH_UA_MOBILE.
     * InputKeyTo(Macro) converts any input key to Macro — sec-ch-ua-mobile → SEC_CH_UA_MOBILE ✓
     */
    #[InputKeyTo(KeyCase::Macro)]
    public string $SEC_CH_UA_MOBILE;

    /** sec-ch-ua-platform → Snake → sec_ch_ua_platform ✓ (class-level handles it) */
    public string $sec_ch_ua_platform;
}
