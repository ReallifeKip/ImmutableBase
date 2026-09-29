<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\Defaults;
use ReallifeKip\ImmutableBase\Enums\Native;

readonly class DocOrder extends DocBase
{
    #[Defaults('trim')]
    public string $mode;
    #[ArrayOf(DocItem::class)]
    public array $lines;
    #[ArrayOf(Size::class)]
    public array $sizes;
    #[ArrayOf(Native::int, DocItem::class)]
    public array $mixedLines;
    public DocEmail|DocItem $contact;
    public ?Quote $quote;
    public DocItem $main;
}
