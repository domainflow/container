<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing an optional intersection-typed parameter whose
 * declared members are all interfaces, so it falls back to its default value.
 */
class DummyWithOptionalIntersection
{
    public $dependency;

    public function __construct(DummyInterfaceC&DummyInterfaceD $dependency = new DummyOnlyCAndD())
    {
        $this->dependency = $dependency;
    }
}
