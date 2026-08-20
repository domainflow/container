<?php

declare(strict_types=1);

namespace DomainFlow;

use ArrayAccess;
use DomainFlow\Container\ContextualBindingBuilder;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use DomainFlow\Container\Trait\ArrayAccessTrait;
use DomainFlow\Container\Trait\BindingManagerTrait;
use DomainFlow\Container\Trait\CacheManagerTrait;
use DomainFlow\Container\Trait\CallableResolutionTrait;
use DomainFlow\Container\Trait\ContextualBindingTrait;
use DomainFlow\Container\Trait\DebuggingTrait;
use DomainFlow\Container\Trait\HookManagerTrait;
use DomainFlow\Container\Trait\PsrContainerTrait;
use DomainFlow\Container\Trait\ReflectionBuilderTrait;
use DomainFlow\Container\Trait\ScopeTrait;
use DomainFlow\Container\Trait\TagManagerTrait;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Throwable;

/**
 * Class Container
 *
 * A dependency injection container implementing PSR-11 and ArrayAccess.
 *
 * @implements ArrayAccess<string, mixed>
 */
class Container implements ContainerInterface, ArrayAccess
{
    use BindingManagerTrait;
    use ContextualBindingTrait;
    use HookManagerTrait;
    use TagManagerTrait;
    use CacheManagerTrait;
    use ReflectionBuilderTrait;
    use PsrContainerTrait;
    use ArrayAccessTrait;
    use ScopeTrait;
    use CallableResolutionTrait;
    use DebuggingTrait;

    /**
     * @var array<class-string, object>
     */
    protected static array $container_instances = [];

    /**
     * Contextual bindings.
     *
     * Format: [ParentClass => [Abstract => Concrete], ...]
     *
     * @var array<string, array<string, string>>
     */
    public array $contextual = [];

    /**
     * Instance-specific cache for ReflectionClass objects.
     *
     *@var array<string, ReflectionClass<object>>
     */
    protected array $reflectionCache = [];

    /**
     * @var array<string, bool>
     */
    public array $resolving = [];

    /**
     * Identifiers currently being resolved in call order.
     *
     * @var list<string>
     */
    protected array $resolutionStack = [];

    /**
     * Returns the singleton instance.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        $class = static::class;

        if (!isset(static::$container_instances[$class])) {
            /** @phpstan-ignore-next-line */
            $instance = new static();
            static::$container_instances[$class] = $instance;
        }

        /** @var static */
        return static::$container_instances[$class];
    }

    /**
     * Set the singleton instance manually.
     *
     * @param object $container
     */
    public static function setInstance(
        object $container
    ): void {
        if (!($container instanceof static)) {
            throw new InvalidArgumentException('Instance must be of type ' . static::class);
        }

        $class = static::class;

        if (isset(static::$container_instances[$class])) {
            throw new LogicException("$class instance is already set.");
        }

        static::$container_instances[$class] = $container;
    }

    /**
     * Resolve an instance of the given abstract type.
     *
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @throws NotFoundException|ContainerException|Throwable
     * @return mixed
     */
    public function make(
        string $abstract,
        array $parameters = []
    ): mixed {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        if (isset($this->resolving[$abstract])) {
            $cycleStart = array_search($abstract, $this->resolutionStack, true) ?: 0;
            $cycle = [...array_slice($this->resolutionStack, $cycleStart), $abstract];

            throw new ContainerException('Circular dependency detected: ' . implode(' -> ', $cycle) . '.');
        }

        $this->resolving[$abstract] = true;
        $this->resolutionStack[] = $abstract;
        try {
            foreach ($this->beforeResolveHooks as $hook) {
                $hook($abstract, $parameters);
            }

            $sharedBinding = isset($this->bindings[$abstract]) && $this->bindings[$abstract]['shared'];
            if (array_key_exists($abstract, $this->instances)) {
                $instance = $this->instances[$abstract];
            } elseif (!isset($this->bindings[$abstract])) {
                if (!class_exists($abstract)) {
                    throw new NotFoundException("No entry found for [$abstract].");
                }
                $instance = $this->build($abstract, $parameters);
            } else {
                $concrete = $this->bindings[$abstract]['concrete'];
                $instance = $concrete($this, $parameters);

            }

            foreach ($this->afterResolveHooks as $hook) {
                $modifiedInstance = $hook(
                    $instance,
                    $abstract,
                    $parameters
                );
                if ($modifiedInstance !== null) {
                    $instance = $modifiedInstance;
                }
            }

            if ($sharedBinding) {
                $this->instances[$abstract] = $instance;
            }

            return $instance;
        } finally {
            unset($this->resolving[$abstract]);
            array_pop($this->resolutionStack);
        }
    }

    /**
     * Provide a contextual binding builder.
     *
     * @param string $concrete
     * @return ContextualBindingBuilder
     */
    public function when(
        string $concrete
    ): ContextualBindingBuilder {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /**
     * Reset (clear) container state.
     *
     * @return void
     */
    public function resetContainer(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolvedServicesCache = [];
        $this->aliases = [];
        $this->cacheableBindings = [];
        $this->contextual = [];
        $this->scopes = [];
        $this->reflectionCache = [];
        $this->resolving = [];
        $this->resolutionStack = [];
        $this->beforeResolveHooks = [];
        $this->afterResolveHooks = [];
        $this->tags = [];
        $this->unionTypePriority = [];
        $this->clearResolutionCache();
    }
}
