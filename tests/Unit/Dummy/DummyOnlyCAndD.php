<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class implementing both DummyInterfaceC and DummyInterfaceD.
 */
class DummyOnlyCAndD implements DummyInterfaceC, DummyInterfaceD
{
    public function test(): string
    {
        return "onlyCAndD";
    }

    public function demo(): string
    {
        return "onlyCAndD";
    }
}
