<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy Not Found.
 */
class DummyNotFound
{
    public $dep;
    public function __construct(DummyInterfaceA $dep)
    {
        $this->dep = $dep;
    }
}
