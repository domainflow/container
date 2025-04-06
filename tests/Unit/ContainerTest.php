<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;
use Throwable;

#[CoversClass(Container::class)]
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
    public function test_make_handles_circular_dependency(): void
    {
        $containerMock = $this->getMockBuilder(Container::class)
            ->onlyMethods(['resolveCircularDependency'])
            ->getMock();

        $containerMock->method('resolveCircularDependency')->willReturn(fn () => 'circular_result');

        $containerMock->resolving['CircularService'] = true;

        $result = $containerMock->make('CircularService');

        $this->assertSame('circular_result', $result());
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
        $this->container->instance('ServiceA', new stdClass());
        $this->assertTrue($this->container->has('ServiceA'));

        $this->container->resetContainer();

        $this->assertFalse($this->container->has('ServiceA'));
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
