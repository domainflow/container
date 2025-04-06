<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyOptionalInterfaceDep
{
    public $dep;
    public function __construct(?DummyInterfaceOptional $dep = null)
    {
        $this->dep = $dep;
    }
}
