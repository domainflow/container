<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use TypeError;

/**
 * Trait BindingManagerTrait
 *
 * Handles binding an abstract type to a concrete implementation,
 * managing singletons, instances, and aliasing.
 */
trait BindingManagerTrait
{
    /**
     * @var array<string, array{concrete: Closure(Container, array<string, mixed>): mixed, shared: bool}>
     */
    protected array $bindings = [];

    /**
     * @var array<string, mixed>
     */
    protected array $instances = [];

    /**
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Bind an abstract type to a concrete implementation.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @param bool $shared
     * @throws TypeError|ContainerException
     * @return void
     */
    public function bind(
        string $abstract,
        Closure|string|null $concrete = null,
        bool $shared = false
    ): void {
        if ($concrete === null) {
            $concrete = $abstract;
        }
        if (!$concrete instanceof Closure) {
            if (!class_exists($concrete)) {
                throw new ContainerException("Class '$concrete' does not exist for binding [$abstract].");
            }
            /** @var class-string $className */
            $className = $concrete;
            $concrete = fn (self $container, array $parameters = []): mixed => $container->build($className, $parameters);
        }
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Register a singleton (shared) binding.
     *
     * @param string $abstract
     * @param Closure|string|null $concrete
     * @throws ContainerException
     * @return void
     */
    public function singleton(
        string $abstract,
        Closure|string|null $concrete = null
    ): void {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind a pre-resolved instance.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(
        string $abstract,
        mixed $instance
    ): void {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Register an alias for an abstract type.
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    public function alias(
        string $abstract,
        string $alias
    ): void {
        $this->aliases[$alias] = $abstract;
    }
}
