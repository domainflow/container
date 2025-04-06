<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithIntersectionConstructor
{
    public DummyIntersection $instance;

    public function __construct(DummyIntersection $instance)
    {
        $this->instance = $instance;
    }
}
