<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\ContextualBindingBuilder;
use DomainFlow\Tests\Unit\Dummy\DummyContextualBinding;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(ContextualBindingBuilder::class)]
final class ContextualBindingTraitTest extends TestCase
{
    private DummyContextualBinding $dummy;
    private Container $container;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->dummy = new DummyContextualBinding();
        $this->container = $this->createMock(Container::class);
    }

    public function test_addContextualBinding_creates_binding_when_not_set(): void
    {
        $concrete = 'MyConcrete';
        $abstract = 'MyAbstract';
        $implementation = 'MyImplementation';
        $this->dummy->addContextualBinding($concrete, $abstract, $implementation);

        $this->assertArrayHasKey($concrete, $this->dummy->contextual);
        $this->assertArrayHasKey($abstract, $this->dummy->contextual[$concrete]);
        $this->assertSame($implementation, $this->dummy->contextual[$concrete][$abstract]);
    }

    public function test_addContextualBinding_overwrites_existing_binding(): void
    {
        $concrete = 'MyConcrete';
        $abstract = 'MyAbstract';
        $initialImplementation = 'InitialImplementation';
        $this->dummy->addContextualBinding($concrete, $abstract, $initialImplementation);

        $newImplementation = 'NewImplementation';
        $this->dummy->addContextualBinding($concrete, $abstract, $newImplementation);

        $this->assertSame($newImplementation, $this->dummy->contextual[$concrete][$abstract]);
    }

    public function test_when_returns_ContextualBindingBuilder(): void
    {
        $container = new Container();
        $builder = $container->when('SomeClass');

        $this->assertInstanceOf(ContextualBindingBuilder::class, $builder);
    }
}
