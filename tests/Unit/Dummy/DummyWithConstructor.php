<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithConstructor
{
    public string $foo;

    public function __construct(string $foo)
    {
        $this->foo = $foo;
    }
}
