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
final class TestableContextualBindingBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_createNeedsBindingBuilder_returns_instance_with_correct_properties(): void
    {
        $containerStub = $this->createStub(Container::class);
        $concrete = 'MyConcreteClass';
        $abstract = 'MyAbstractInterface';

        $builder = new TestableContextualBindingBuilder($containerStub, $concrete);
        $needsBuilder = $builder->publicCreateNeedsBindingBuilder($abstract);

        $reflection = new ReflectionClass($needsBuilder);

        $propConcrete = $reflection->getProperty('concrete');

        $this->assertSame($concrete, $propConcrete->getValue($needsBuilder));

        $propAbstract = $reflection->getProperty('abstract');

        $this->assertSame($abstract, $propAbstract->getValue($needsBuilder));
    }
}

// dummy class
class TestableContextualBindingBuilder extends ContextualBindingBuilder
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
