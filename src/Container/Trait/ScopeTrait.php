<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\NotFoundException;

/**
 * Trait ScopeTrait
 *
 * Provides the ability to create sub-containers (scopes) that
 * have their own bindings and instances but fall back to the parent.
 */
trait ScopeTrait
{
    /**
     * @var array<string, self> Named subcontainers for scopes.
     */
    protected array $scopes = [];

    /**
     * Create or retrieve a lazy-scoped subcontainer.
     *
     * The subcontainer has its own instance store but falls back to the parent.
     *
     * @param string $scopeName
     * @param callable(Container): mixed $callback
     * @return mixed
     */
    public function scope(
        string $scopeName,
        callable $callback
    ): mixed {
        if (!isset($this->scopes[$scopeName])) {
            $this->scopes[$scopeName] = new class($this) extends Container {
                protected Container $parent;
                public function __construct(Container $parent)
                {
                    $this->parent = $parent;
                }

                /**
                 * @param mixed $id
                 * @return bool
                 */
                public function has(mixed $id): bool
                {
                    $id = Container::keyToString($id);

                    return isset($this->bindings[$id])
                        || isset($this->instances[$id])
                        || $this->parent->has($id);
                }

                /**
                 * @param mixed $id
                 * @return mixed
                 */
                public function get(mixed $id): mixed
                {
                    $id = Container::keyToString($id);
                    if (isset($this->instances[$id])) {
                        return $this->instances[$id];
                    }
                    if (isset($this->bindings[$id])) {
                        return parent::get($id);
                    }
                    if ($this->parent->has($id)) {
                        return $this->parent->get($id);
                    }
                    throw new NotFoundException("No entry found for [$id] in scope or parent.");
                }

                /**
                 * @param string $abstract
                 * @param array<string, mixed> $parameters
                 * @return mixed
                 */
                public function make(string $abstract, array $parameters = []): mixed
                {
                    $abstract = $this->aliases[$abstract] ?? $abstract;
                    if (isset($this->instances[$abstract])) {
                        return $this->instances[$abstract];
                    }
                    if (isset($this->bindings[$abstract])) {
                        return parent::make($abstract, $parameters);
                    }
                    if ($this->parent->has($abstract)) {
                        return $this->parent->make($abstract, $parameters);
                    }
                    throw new NotFoundException("Cannot find abstract [$abstract] in scope or parent.");
                }

            };
        }

        return $callback($this->scopes[$scopeName]);
    }
}
