<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class for contextual binding testing.
 */
class DummyContextual
{
    public $dep;
    public function __construct(DummyNoConstructor $dep)
    {
        $this->dep = $dep;
    }
}
