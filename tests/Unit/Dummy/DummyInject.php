<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container\Attribute\Inject;

/**
 * A dummy class for testing property injection.
 */
class DummyInject
{
    #[Inject]
    public DummyNoConstructor $dependency;
}
