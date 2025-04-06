<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * An alternate implementation for DummyNoConstructor.
 */
class DummyAlternateNoConstructor extends DummyNoConstructor
{
    public string $foo = 'alternate';
}
