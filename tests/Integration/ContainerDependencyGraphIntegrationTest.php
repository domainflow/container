<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use ReflectionException;

#[CoversNothing]
class ContainerDependencyGraphIntegrationTest extends TestCase
{
    /**
     * @throws ReflectionException| ContainerException
     */
    public function test_dependency_graph_is_generated_correctly(): void
    {
        $container = new Container();

        $container->bind(ServiceA::class);
        $container->bind(ServiceB::class);
        $container->bind(ServiceC::class);

        $graph = $container->generateDependencyGraph();

        $this->assertArrayHasKey(ServiceA::class, $graph);
        $this->assertArrayHasKey(ServiceB::class, $graph);
        $this->assertArrayHasKey(ServiceC::class, $graph);

        $this->assertSame(
            ['kind' => 'class', 'dependencies' => [ServiceB::class]],
            $graph[ServiceA::class],
            'ServiceA should depend on ServiceB.'
        );
        $this->assertSame(
            ['kind' => 'class', 'dependencies' => [ServiceC::class]],
            $graph[ServiceB::class],
            'ServiceB should depend on ServiceC.'
        );
        $this->assertSame(
            ['kind' => 'class', 'dependencies' => []],
            $graph[ServiceC::class],
            'ServiceC should have no dependencies.'
        );
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_dependency_graph_does_not_open_a_connection_registered_via_a_dynamic_binding(): void
    {
        $container = new Container();
        $connectionOpened = false;

        $container->bind(ServiceA::class);
        $container->singleton(FakeConnection::class, function () use (&$connectionOpened): FakeConnection {
            $connectionOpened = true;

            return new FakeConnection();
        });

        $graph = $container->generateDependencyGraph();

        $this->assertFalse($connectionOpened, 'A diagnostic call must not open a connection owned by a dynamic factory.');
        $this->assertSame(
            ['kind' => 'class', 'dependencies' => [ServiceB::class]],
            $graph[ServiceA::class]
        );
        $this->assertSame(
            ['kind' => 'dynamic', 'dependencies' => []],
            $graph[FakeConnection::class]
        );
    }
}

// dummy classes
class ServiceA
{
    public function __construct(
        ServiceB $b
    ) {
    }
}

class ServiceB
{
    public function __construct(
        ServiceC $c
    ) {
    }
}

class ServiceC
{
}

class FakeConnection
{
}
