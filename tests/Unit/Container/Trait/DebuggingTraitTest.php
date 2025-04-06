<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;

#[CoversClass(Container::class)]
final class DebuggingTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_returns_correct_structure(): void
    {
        $this->container->bind('ServiceA', fn () => new stdClass());
        $this->container->bind('ServiceB', fn () => new stdClass());

        $graph = $this->container->generateDependencyGraph();

        $this->assertArrayHasKey('ServiceA', $graph);
        $this->assertArrayHasKey('ServiceB', $graph);
        $this->assertIsArray($graph['ServiceA']);
        $this->assertIsArray($graph['ServiceB']);
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_includes_constructor_parameters(): void
    {
        $this->container->bind('TestService', function () {
            return new class('test', 123) {
                public function __construct(
                    string $param1,
                    int $param2
                ) {
                }
            };
        });

        $graph = $this->container->generateDependencyGraph();

        $this->assertArrayHasKey('TestService', $graph);
        $this->assertContains('string', $graph['TestService']);
        $this->assertContains('int', $graph['TestService']);
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_throws_exception_for_invalid_class_string(): void
    {
        // Bind a service that returns a string for which class does not exist.
        $this->container->bind('FaultyService', fn ($c, array $params = []) => 'NonExistentClass');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class NonExistentClass does not exist.");

        // This call should trigger the exception since the binding returns an invalid class name.
        $this->container->generateDependencyGraph();
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_throws_exception_for_invalid_instance_type(): void
    {
        // Bind a service that returns a value that is neither an object nor a string.
        $this->container->bind('FaultyService2', fn ($c, array $params = []) => 42);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expected a valid class name or object for ReflectionClass.");

        // This call should trigger the exception since 42 is not acceptable.
        $this->container->generateDependencyGraph();
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_string_instance_branch(): void
    {
        // Bind a service that returns a valid class name (as a string)
        $this->container->bind('StringService', fn ($c, array $params = []) => stdClass::class);

        $graph = $this->container->generateDependencyGraph();

        $this->assertArrayHasKey('StringService', $graph);
        // Since stdClass has no constructor, its dependencies array should be empty.
        $this->assertSame([], $graph['StringService']);
    }
}
