<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit;

use DomainFlow\Container;
use DomainFlow\Container\Cache\InMemoryContainerCache;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use DomainFlow\Tests\Unit\Dummy\DummyNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithConstructor;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use stdClass;
use Throwable;

#[CoversClass(Container::class)]
#[CoversClass(InMemoryContainerCache::class)]
final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $ref = new ReflectionClass(Container::class);
        $prop = $ref->getProperty('container_instances');
        $prop->setValue(null, []);

        $this->container = new Container();
    }

    protected function tearDown(): void
    {
        $ref = new ReflectionClass(Container::class);
        $prop = $ref->getProperty('container_instances');
        $prop->setValue(null, []);
    }

    public function test_getInstance_returns_singleton(): void
    {
        $instance1 = Container::getInstance();
        $instance2 = Container::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * @throws ContainerException|Throwable|NotFoundException
     */
    public function test_make_creates_new_instance(): void
    {
        $instance = $this->container->make(Container::class);

        $this->assertInstanceOf(Container::class, $instance);
    }

    /**
     * @throws ContainerException|Throwable|NotFoundException
     */
    public function test_make_throws_NotFoundException_for_invalid_class(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("No entry found for [InvalidClass].");

        $this->container->make('InvalidClass');
    }

    /**
     * @throws ContainerException|Throwable|NotFoundException
     */
    public function test_make_clears_the_resolution_state_after_a_circular_dependency_failure(): void
    {
        $this->container->bind('first', fn (Container $container) => $container->make('second'));
        $this->container->bind('second', fn (Container $container) => $container->make('first'));

        try {
            $this->container->make('first');
            $this->fail('Expected a circular dependency to throw.');
        } catch (ContainerException) {
            $this->assertSame([], $this->container->resolving);
        }
    }

    /**
     * @throws ContainerException|Throwable|NotFoundException
     */
    public function test_make_stores_singletons_correctly(): void
    {
        $this->container->singleton('SingletonService', fn () => new stdClass());

        $instance1 = $this->container->make('SingletonService');
        $instance2 = $this->container->make('SingletonService');

        $this->assertSame($instance1, $instance2);
    }

    public function test_resetContainer_clears_bindings_and_instances(): void
    {
        $cache = new InMemoryContainerCache();
        $this->container->setExternalCache($cache);
        $this->container->instance('ServiceA', new stdClass());
        $this->assertTrue($this->container->has('ServiceA'));

        $this->container->resetContainer();

        $this->assertFalse($this->container->has('ServiceA'));
        $this->assertFalse($cache->has('domainflow.container.definitions.v1'));
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_make_executes_beforeResolveHooks(): void
    {
        $this->container->bind('TestService', fn () => new stdClass());

        $executed = false;

        $this->container->addBeforeResolve(function (string $abstract, array $parameters) use (&$executed) {
            if ($abstract === 'TestService') {
                $executed = true;
            }
        });

        $this->container->make('TestService');

        $this->assertTrue($executed, "The beforeResolve hook should execute before making an instance.");
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_make_executes_afterResolveHooks_and_allows_modification(): void
    {
        $this->container->bind('TestService', fn () => new stdClass());

        $modifiedInstance = new stdClass();
        $executed = false;

        $this->container->addAfterResolve(function (object $instance, string $abstract, array $parameters) use (&$executed, $modifiedInstance) {
            if ($abstract === 'TestService') {
                $executed = true;

                return $modifiedInstance;
            }

            return null;
        });

        $instance = $this->container->make('TestService');

        $this->assertTrue($executed, "The afterResolve hook should execute after making an instance.");
        $this->assertSame($modifiedInstance, $instance, "The afterResolve hook should allow modifying the instance.");
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_make_runs_hooks_once_for_each_supported_resolution_path(): void
    {
        $instance = new stdClass();
        $this->container->bind('bound', fn (): stdClass => new stdClass());
        $this->container->singleton('shared', fn (): stdClass => new stdClass());
        $this->container->instance('instance', $instance);

        $paths = [
            'bound' => [],
            'shared' => [],
            'instance' => [],
            DummyWithConstructor::class => ['foo' => 'value'],
            DummyNoConstructor::class => [],
        ];

        foreach ($paths as $abstract => $parameters) {
            $beforeCalls = 0;
            $afterCalls = 0;
            $processedObjects = [];
            $this->container->addBeforeResolve(
                function (string $resolved) use (&$beforeCalls, $abstract): void {
                    if ($resolved === $abstract) {
                        $beforeCalls++;
                    }
                }
            );
            $this->container->addAfterResolve(
                function (object $resolved, string $identifier) use (&$afterCalls, &$processedObjects, $abstract): ?object {
                    if ($identifier === $abstract) {
                        $afterCalls++;
                        $processedObjects[spl_object_id($resolved)] = ($processedObjects[spl_object_id($resolved)] ?? 0) + 1;
                    }

                    return null;
                }
            );

            $this->container->make($abstract, $parameters);

            $this->assertSame(1, $beforeCalls, "[$abstract] should emit one before-resolve hook.");
            $this->assertSame(1, $afterCalls, "[$abstract] should emit one after-resolve hook.");
            $this->assertSame([1], array_values($processedObjects), "[$abstract] should not process its resolved object twice.");
        }
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_make_runs_only_before_hooks_when_resolution_fails(): void
    {
        $beforeCalls = 0;
        $afterCalls = 0;
        $this->container->bind('failing', static function (): never {
            throw new RuntimeException('Factory failed.');
        });
        $this->container->addBeforeResolve(static function (string $abstract) use (&$beforeCalls): void {
            if ($abstract === 'failing') {
                $beforeCalls++;
            }
        });
        $this->container->addAfterResolve(static function (object $instance, string $abstract) use (&$afterCalls): ?object {
            if ($abstract === 'failing') {
                $afterCalls++;
            }

            return null;
        });

        try {
            $this->container->make('failing');
            $this->fail('Expected the failing factory to throw.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Factory failed.', $exception->getMessage());
        }

        $this->assertSame(1, $beforeCalls);
        $this->assertSame(0, $afterCalls);
    }

    public function test_setInstance_sets_singleton_instance(): void
    {
        $container = new Container();
        Container::setInstance($container);

        $this->assertSame($container, Container::getInstance());
    }

    public function test_setInstance_throws_if_already_set(): void
    {
        $container = new Container();
        Container::setInstance($container);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('DomainFlow\Container instance is already set.');

        Container::setInstance(new Container());
    }

    public function test_setInstance_throws_for_invalid_instance(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Instance must be of type DomainFlow\Container');

        Container::setInstance(new stdClass());
    }
}
