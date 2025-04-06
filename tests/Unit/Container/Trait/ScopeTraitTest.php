<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use stdClass;
use Throwable;

#[CoversClass(Container::class)]
final class ScopeTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
    }

    public function test_scope_creates_new_container_scope(): void
    {
        $scopedInstance = $this->container->scope('test_scope', function ($scopedContainer) {
            return $scopedContainer;
        });

        $this->assertInstanceOf(Container::class, $scopedInstance);
        $this->assertNotSame($this->container, $scopedInstance);
    }

    public function test_scope_has_checks_bindings_and_instances(): void
    {
        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $scopedContainer->instance('ScopedService', new stdClass());

        $this->assertTrue($scopedContainer->has('ScopedService'));
        $this->assertFalse($scopedContainer->has('UnknownService'));
    }

    public function test_scope_get_returns_instance_from_scope(): void
    {
        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $scopedInstance = new stdClass();
        $scopedContainer->instance('ScopedService', $scopedInstance);

        $retrievedInstance = $scopedContainer->get('ScopedService');
        $this->assertSame($scopedInstance, $retrievedInstance);
    }

    public function test_scope_make_returns_instance_from_scope(): void
    {
        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $scopedInstance = new stdClass();
        $scopedContainer->instance('ScopedService', $scopedInstance);

        $retrievedInstance = $scopedContainer->make('ScopedService');
        $this->assertSame($scopedInstance, $retrievedInstance);
    }

    /**
     * @throws ContainerException
     */
    public function test_scope_make_falls_back_to_parent(): void
    {
        $this->container->bind('ParentMadeService', fn () => new stdClass());

        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $instance = $scopedContainer->make('ParentMadeService');
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function test_scope_make_throws_exception_when_not_found(): void
    {
        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("Cannot find abstract [NonExistentService] in scope or parent.");

        $scopedContainer->make('NonExistentService');
    }

    public function test_scope_get_throws_exception_when_not_found(): void
    {
        $scopedContainer = $this->container->scope('test_scope', fn ($scoped) => $scoped);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("No entry found for [UnknownService] in scope or parent.");

        $scopedContainer->get('UnknownService');
    }

    public function test_scopeDelegateMakeCallsParentMake(): void
    {
        $parent = new Container();
        $expected = new stdClass();

        $parent->instance('SharedService', $expected);
        $scoped = $parent->scope('test_scope', fn ($scopedContainer) => $scopedContainer);

        $scoped->bind('BoundService', fn () => 'delegated');

        $this->assertSame('delegated', $scoped->make('BoundService'));
    }

    public function test_scopeGetCallsParentGet(): void
    {
        $parent = new Container();
        $expectedValue = new stdClass();
        $parent->instance('TestService', $expectedValue);

        $scoped = $parent->scope('test_scope', fn ($scopedContainer) => $scopedContainer);

        $this->assertSame($expectedValue, $scoped->get('TestService'));
    }

    public function test_scopeGetUsesParentGetWhenBindingExists(): void
    {
        $parent = new ScobeTestableContainer();
        $scoped = $parent->scope('test_scope', fn ($scopedContainer) => $scopedContainer);

        $scoped->bind('ScopedBoundService', fn () => 'scopedBound');

        $this->assertSame('scopedBound', $scoped->get('ScopedBoundService'));
    }
}

# dummy class
class ScobeTestableContainer extends Container
{
    /**
     * @param array<string, mixed> $parameters
     * @throws ContainerException|NotFoundException|ReflectionException|Throwable
     */
    public function testCall(
        mixed $callable,
        array $parameters = []
    ): mixed {
        return $this->doCall($callable, $parameters);
    }
}
