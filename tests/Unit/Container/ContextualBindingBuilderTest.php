<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container;

use DomainFlow\Container;
use DomainFlow\Container\ContextualBindingBuilder;
use DomainFlow\Container\NeedsBindingBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContextualBindingBuilder::class)]
#[CoversClass(NeedsBindingBuilder::class)]
final class ContextualBindingBuilderTest extends TestCase
{
    /**
     * `createNeedsBindingBuilder()` is documented as overridable so that
     * advanced consumers can customize or stub out the builder in tests.
     *
     * @throws Exception
     */
    public function test_needs_delegates_to_an_overridden_createNeedsBindingBuilder(): void
    {
        $containerStub = $this->createStub(Container::class);
        $concrete = 'MyConcreteClass';
        $abstract = 'MyAbstractInterface';

        $builder = new class($containerStub, $concrete) extends ContextualBindingBuilder {
            protected function createNeedsBindingBuilder(string $abstract): NeedsBindingBuilder
            {
                return new DummyNeedsBindingBuilder($abstract);
            }
        };

        $needsBuilder = $builder->needs($abstract);
        $this->assertInstanceOf(DummyNeedsBindingBuilder::class, $needsBuilder);
        $this->assertSame($abstract, $needsBuilder->getAbstract());
    }

    /**
     * @throws Exception
     */
    public function test_needs_give_registers_the_contextual_binding_on_the_container(): void
    {
        $concrete = 'MyConcreteClass';
        $abstract = 'MyAbstractInterface';
        $implementation = 'MyImplementationClass';

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
            ->method('addContextualBinding')
            ->with($concrete, $abstract, $implementation);

        $builder = new ContextualBindingBuilder($containerMock, $concrete);
        $builder->needs($abstract)->give($implementation);
    }
}

// dummy class
class DummyNeedsBindingBuilder extends NeedsBindingBuilder
{
    protected string $abstract;

    /**
     * @param string $abstract
     */
    public function __construct(
        string $abstract
    ) {
        $this->abstract = $abstract;
    }

    /**
     * @return string
     */
    public function getAbstract(): string
    {
        return $this->abstract;
    }

    /**
     * @param string $implementation
     * @return void
     */
    public function give(
        string $implementation
    ): void {
    }
}
