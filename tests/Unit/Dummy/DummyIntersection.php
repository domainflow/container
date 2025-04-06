<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing intersection types.
 */
class DummyIntersection implements DummyInterfaceA, DummyInterfaceB
{
    public function foo(): string
    {
        return "foo";
    }

    public function bar(): string
    {
        return "bar";
    }
}
