<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class with no constructor for after-resolve hook testing.
 */
class DummyAfterResolveNoConstructor
{
    public string $foo = 'original';
}
