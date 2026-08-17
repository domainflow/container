<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing an intersection type whose concrete class member
 * does not implement the other declared interface member.
 */
class DummyWithIntersectionUnsatisfiedInterface
{
    public $dependency;

    public function __construct(DummyNoConstructor&DummyInterfaceA $dependency)
    {
        $this->dependency = $dependency;
    }
}
