<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing an intersection type whose declared members
 * include one concrete class and one interface it implements.
 */
class DummyWithIntersectionOfClassAndInterface
{
    public $dependency;

    public function __construct(DummyOnlyC&DummyInterfaceC $dependency)
    {
        $this->dependency = $dependency;
    }
}
