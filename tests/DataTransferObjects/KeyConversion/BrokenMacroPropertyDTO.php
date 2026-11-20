<?php

declare(strict_types=1);

namespace Tests\DataTransferObjects\KeyConversion;

use ReallifeKip\ImmutableBase\Attributes\InputCaseTo;
use ReallifeKip\ImmutableBase\Enums\KeyCase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;

/**
 * Intentionally broken fixture: demonstrates that a MACRO_CASE property name
 * is unreachable when only class-level InputCaseTo(Snake) is applied.
 *
 * Class Snake converts 'sec-ch-ua-mobile' → 'sec_ch_ua_mobile', which does
 * NOT match the property name SEC_CH_UA_MOBILE. Without a property-level
 * InputCaseTo(Kebab) override, fromArray() will throw RequiredValueException
 * for SEC_CH_UA_MOBILE.
 */
#[InputCaseTo(KeyCase::Snake)]
readonly class BrokenMacroPropertyDTO extends DataTransferObject
{
    public string $cache_control;

    /** MACRO_CASE name — intentionally missing property-level InputCaseTo. */
    public string $SEC_CH_UA_MOBILE;
}
