<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Tests\Unit\Dummy\DummyInterfaceA;
use DomainFlow\Tests\Unit\Dummy\DummyInterfaceB;
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
    public function test_generateDependencyGraph_does_not_invoke_closure_factories(): void
    {
        $invoked = false;
        $this->container->bind('ServiceA', function () use (&$invoked) {
            $invoked = true;

            return new stdClass();
        });

        $graph = $this->container->generateDependencyGraph();

        $this->assertFalse($invoked, 'generateDependencyGraph() must not execute a binding factory.');
        $this->assertArrayHasKey('ServiceA', $graph);
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_reports_closure_bindings_as_dynamic(): void
    {
        $this->container->bind('ServiceA', fn () => new stdClass());

        $graph = $this->container->generateDependencyGraph();

        $this->assertSame(['kind' => 'dynamic', 'dependencies' => []], $graph['ServiceA']);
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_introspects_class_string_bindings_without_instantiating(): void
    {
        $this->container->bind('TestService', DummyConstructedWithSentinel::class);

        $graph = $this->container->generateDependencyGraph();

        $this->assertSame(0, DummyConstructedWithSentinel::$constructedCount, 'The class-string binding must not be instantiated.');
        $this->assertSame(
            ['kind' => 'class', 'dependencies' => ['string', 'int']],
            $graph['TestService']
        );
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_preserves_union_type_representation(): void
    {
        $this->container->bind('UnionService', DummyWithUnionParameter::class);

        $graph = $this->container->generateDependencyGraph();

        $this->assertSame(['kind' => 'class', 'dependencies' => ['string|int']], $graph['UnionService']);
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_preserves_intersection_type_representation(): void
    {
        $this->container->bind('IntersectionService', DummyWithIntersectionParameter::class);

        $graph = $this->container->generateDependencyGraph();

        $this->assertSame(
            ['kind' => 'class', 'dependencies' => [DummyInterfaceA::class . '&' . DummyInterfaceB::class]],
            $graph['IntersectionService']
        );
    }

    /**
     * @throws ReflectionException|ContainerException
     */
    public function test_generateDependencyGraph_reports_no_dependencies_for_constructor_less_class(): void
    {
        $this->container->bind('StringService', stdClass::class);

        $graph = $this->container->generateDependencyGraph();

        $this->assertSame(['kind' => 'class', 'dependencies' => []], $graph['StringService']);
    }
}

final class DummyConstructedWithSentinel
{
    public static int $constructedCount = 0;

    public function __construct(
        string $param1,
        int $param2
    ) {
        self::$constructedCount++;
    }
}

final class DummyWithUnionParameter
{
    public function __construct(string|int $parameter)
    {
    }
}

final class DummyWithIntersectionParameter
{
    public function __construct(DummyInterfaceA&DummyInterfaceB $parameter)
    {
    }
}
