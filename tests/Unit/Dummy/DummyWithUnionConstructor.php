<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithUnionConstructor
{
    public DummyUnionA $dependency;

    public function __construct(DummyUnionA|int $dependency)
    {
        // If an int is passed, simulate resolution by instantiating DummyUnionA.
        $this->dependency = is_int($dependency) ? new DummyUnionA() : $dependency;
    }
}
