<?php

declare (strict_types = 1);

namespace Tests\Regressions\Objects\Writer;

enum Quote: string
{
    case Apostrophe = "it's";
    case Backslash  = 'a\\b';
    case Newline    = "line\nbreak";
}
