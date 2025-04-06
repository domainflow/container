<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container;

use DomainFlow\Container;
use DomainFlow\Container\ContextualBindingBuilder;
use DomainFlow\Container\NeedsBindingBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(ContextualBindingBuilder::class)]
#[CoversClass(NeedsBindingBuilder::class)]
final class ContextualBindingBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_needs_returns_correct_instance_without_covering_NeedsBindingBuilder(): void
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
    public function test_createNeedsBindingBuilder_returns_instance_with_correct_properties(): void
    {
        $containerStub = $this->createStub(Container::class);
        $concrete = 'MyConcreteClass';
        $abstract = 'MyAbstractInterface';

        $builder = new AnotherTestableContextualBindingBuilder($containerStub, $concrete);
        $needsBuilder = $builder->publicCreateNeedsBindingBuilder($abstract);

        $reflection = new ReflectionClass($needsBuilder);

        $propConcrete = $reflection->getProperty('concrete');

        $this->assertSame($concrete, $propConcrete->getValue($needsBuilder));

        $propAbstract = $reflection->getProperty('abstract');

        $this->assertSame($abstract, $propAbstract->getValue($needsBuilder));
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

class AnotherTestableContextualBindingBuilder extends ContextualBindingBuilder
{
    /**
     * @param string $abstract
     * @return NeedsBindingBuilder
     */
    public function publicCreateNeedsBindingBuilder(
        string $abstract
    ): NeedsBindingBuilder {
        return $this->createNeedsBindingBuilder($abstract);
    }
}
