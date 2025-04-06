<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container;

/**
 * Dummy class to test BindingManagerTrait and related traits.
 */
class DummyBindingManager extends Container
{
    /**
     * Simple build implementation for testing.
     *
     * @param class-string $concrete
     * @param array<string, mixed> $parameters
     * @return mixed
     */
    public function build(
        string $concrete,
        array $parameters = []
    ): mixed {
        return new $concrete(...$parameters);
    }
}
