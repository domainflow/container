<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class for contextual binding testing.
 */
class DummyContextual
{
    public $dep;
    public function __construct(DummyAlternateNoConstructor $dep)
    {
        $this->dep = $dep;
    }
}
