<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container\Attribute\Inject;

/**
 * A final dummy class with a private property for injection.
 */
final class DummyFinalPrivateInject
{
    #[Inject]
    private DummyNoConstructor $dependency;

    public function getDependency(): DummyNoConstructor
    {
        return $this->dependency;
    }
}
