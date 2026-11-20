<?php

declare (strict_types = 1);

namespace ReallifeKip\ImmutableBase\Exceptions\DefinitionExceptions;

use ReallifeKip\ImmutableBase\Exceptions\DefinitionException;

/**
 * Thrown at scan time when a class or property still carries an attribute
 * that was renamed in 5.0 (#[InputKeyTo] → #[InputCaseTo], #[OutputKeyTo] →
 * #[OutputCaseTo]). PHP never resolves an attribute ImmutableBase does not
 * look up, so without this check the old name would be ignored silently.
 */
class RemovedAttributeException extends DefinitionException
{
    /**
     * @param string $removed     The old attribute short name (e.g. 'InputKeyTo').
     * @param string $replacement The attribute to use instead (e.g. 'InputCaseTo').
     * @param string $target      Human-readable location (e.g. 'Foo::class' or 'Foo::$bar').
     */
    public function __construct(string $removed, string $replacement, string $target)
    {
        parent::__construct("#[{$removed}] on {$target} was renamed to #[{$replacement}] in 5.0; see UPGRADE.md");
    }
}
