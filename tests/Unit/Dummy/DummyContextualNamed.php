<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * DummyContextualNamed
 */
class DummyContextualNamed
{
    public $dep;
    public function __construct(DummyNoConstructor $dep)
    {
        $this->dep = $dep;
    }
}
