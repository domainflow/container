<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithIntersectionConstructorFail
{
    public $dependency;
    public function __construct(DummyInterfaceC&DummyInterfaceD $dependency)
    {
        $this->dependency = $dependency;
    }
}
