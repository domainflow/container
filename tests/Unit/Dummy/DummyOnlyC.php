<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyOnlyC implements DummyInterfaceC
{
    public function test(): string
    {
        return "onlyC";
    }
}
