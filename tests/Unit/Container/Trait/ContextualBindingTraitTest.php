<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\ContextualBindingBuilder;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use DomainFlow\Tests\Unit\Dummy\DummyAlternateNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyContextualNamed;
use DomainFlow\Tests\Unit\Dummy\DummyNoConstructor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversClass(Container::class)]
#[CoversClass(ContextualBindingBuilder::class)]
final class ContextualBindingTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_addContextualBinding_overrides_resolution_for_the_declaring_class(): void
    {
        $this->container->addContextualBinding(
            DummyContextualNamed::class,
            DummyNoConstructor::class,
            DummyAlternateNoConstructor::class
        );

        $instance = $this->container->build(DummyContextualNamed::class);

        $this->assertInstanceOf(DummyAlternateNoConstructor::class, $instance->dep);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_addContextualBinding_overwrites_an_existing_binding(): void
    {
        $this->container->addContextualBinding(
            DummyContextualNamed::class,
            DummyNoConstructor::class,
            DummyAlternateNoConstructor::class
        );
        $this->container->addContextualBinding(
            DummyContextualNamed::class,
            DummyNoConstructor::class,
            DummyNoConstructor::class
        );

        $instance = $this->container->build(DummyContextualNamed::class);

        $this->assertNotInstanceOf(DummyAlternateNoConstructor::class, $instance->dep);
        $this->assertInstanceOf(DummyNoConstructor::class, $instance->dep);
    }

    public function test_when_returns_ContextualBindingBuilder(): void
    {
        $builder = $this->container->when('SomeClass');

        $this->assertInstanceOf(ContextualBindingBuilder::class, $builder);
    }
}
