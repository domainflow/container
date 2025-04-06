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

        $this->assertSame([ServiceB::class], $graph[ServiceA::class], "ServiceA should depend on ServiceB.");
        $this->assertSame([ServiceC::class], $graph[ServiceB::class], "ServiceB should depend on ServiceC.");
        $this->assertSame([], $graph[ServiceC::class], "ServiceC should have no dependencies.");
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
