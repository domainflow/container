<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container;

use DomainFlow\Container;
use DomainFlow\Container\NeedsBindingBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(NeedsBindingBuilder::class)]
final class NeedsBindingBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_give_calls_addContextualBinding_correctly(): void
    {
        $concrete = 'ConcreteClass';
        $abstract = 'AbstractClass';
        $implementation = 'ImplementationClass';

        $containerMock = $this->createMock(Container::class);
        $containerMock->expects($this->once())
            ->method('addContextualBinding')
            ->with(
                $this->equalTo($concrete),
                $this->equalTo($abstract),
                $this->equalTo($implementation)
            );

        $builder = new NeedsBindingBuilder($containerMock, $concrete, $abstract);
        $builder->give($implementation);
    }
}
