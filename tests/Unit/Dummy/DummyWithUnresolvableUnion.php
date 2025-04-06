<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithUnresolvableUnion
{
    public $dependency;
    public function __construct(DummyUnresolvable1|DummyUnresolvable2 $dependency)
    {
        $this->dependency = $dependency;
    }
}
