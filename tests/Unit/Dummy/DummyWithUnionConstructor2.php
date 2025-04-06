<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithUnionConstructor2
{
    public object $dependency;
    public function __construct(DummyUnionA|DummyUnionB $dependency)
    {
        $this->dependency = $dependency;
    }
}
