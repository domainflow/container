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
                    $id = $this->aliases[$id] ?? $id;

                    return isset($this->bindings[$id])
                        || array_key_exists($id, $this->instances)
                        || $this->parent->has($id);
                }

                /**
                 * @param mixed $id
                 * @return mixed
                 */
                public function get(mixed $id): mixed
                {
                    $id = Container::keyToString($id);
                    $id = $this->aliases[$id] ?? $id;
                    if (array_key_exists($id, $this->instances)) {
                        return $this->instances[$id];
                    }
                    if (isset($this->bindings[$id])) {
                        return parent::get($id);
                    }
                    $parentId = $this->parent->aliases[$id] ?? $id;
                    if (isset($this->parent->bindings[$parentId]) || array_key_exists($parentId, $this->parent->instances)) {
                        return $this->parent->get($id);
                    }
                    if (!class_exists($id)) {
                        throw new NotFoundException("No entry found for [$id] in scope or parent.");
                    }

                    return parent::get($id);
                }

                /**
                 * @param string $abstract
                 * @param array<string, mixed> $parameters
                 * @return mixed
                 */
                public function make(string $abstract, array $parameters = []): mixed
                {
                    $abstract = $this->aliases[$abstract] ?? $abstract;
                    if (array_key_exists($abstract, $this->instances)) {
                        return $this->instances[$abstract];
                    }
                    if (isset($this->bindings[$abstract])) {
                        return parent::make($abstract, $parameters);
                    }
                    $parentAbstract = $this->parent->aliases[$abstract] ?? $abstract;
                    if (isset($this->parent->bindings[$parentAbstract]) || array_key_exists($parentAbstract, $this->parent->instances)) {
                        return $this->parent->make($abstract, $parameters);
                    }
                    if (!class_exists($abstract)) {
                        throw new NotFoundException("Cannot find abstract [$abstract] in scope or parent.");
                    }

                    return parent::make($abstract, $parameters);
                }

            };
        }

        return $callback($this->scopes[$scopeName]);
    }

    /**
     * Discard retained values in a named scope while preserving its bindings and
     * other local configuration.
     */
    public function resetScope(string $scopeName): void
    {
        if (!isset($this->scopes[$scopeName])) {
            return;
        }

        $this->scopes[$scopeName]->resetScopeState();
    }

    /**
     * Remove a named scope and all of its local configuration and retained
     * values. A later call to scope() with the same name creates a fresh scope.
     */
    public function disposeScope(string $scopeName): void
    {
        unset($this->scopes[$scopeName]);
    }

    /**
     * Clear only state created while resolving within this scope.
     */
    protected function resetScopeState(): void
    {
        $this->instances = [];
        $this->resolvedServicesCache = [];
        $this->resolving = [];
        $this->resolutionStack = [];
    }
}
