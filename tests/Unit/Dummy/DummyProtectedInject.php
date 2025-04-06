<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container\Attribute\Inject;

/**
 * Dummy class for testing.
 */
class DummyProtectedInject
{
    #[Inject]
    protected DummyNoConstructor $dependency;

    public function getDependency(): DummyNoConstructor
    {
        return $this->dependency;
    }
}
