<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container\Trait\CircularDependencyResolverTrait;

/**
 * Dummy class for testing CircularDependencyResolverTrait.
 */
class DummyCircularResolver
{
    use CircularDependencyResolverTrait;

    /**
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @return object
     */
    public function publicResolveCircularDependency(
        string $abstract,
        array $parameters = []
    ): object {
        return $this->resolveCircularDependency($abstract, $parameters);
    }
}
