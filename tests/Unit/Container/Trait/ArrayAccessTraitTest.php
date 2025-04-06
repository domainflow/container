<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Shared;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(Shared::class)]
final class ArrayAccessTraitTest extends TestCase
{
    /**
     * @throws ContainerException
     */
    public function test_offsetSetAndGet(): void
    {
        $container = new Container();
        $container->offsetSet('arrayKey', 'arrayValue');

        $this->assertTrue($container->offsetExists('arrayKey'));
        $this->assertSame('arrayValue', $container->offsetGet('arrayKey'));
    }

    /**
     * @throws ContainerException
     */
    public function test_offsetSetWithShared(): void
    {
        $container = new Container();
        $shared = new Shared('sharedValue');

        $container->offsetSet('sharedKey', $shared);
        $this->assertTrue($container->offsetExists('sharedKey'));
        $this->assertSame('sharedValue', $container->offsetGet('sharedKey'));
    }

    /**
     * @throws ContainerException
     */
    public function test_offsetUnset(): void
    {
        $container = new Container();
        $container->offsetSet('tempKey', 'tempValue');

        $this->assertTrue($container->offsetExists('tempKey'));
        $this->assertSame('tempValue', $container->offsetGet('tempKey'));

        $container->offsetUnset('tempKey');

        $this->assertFalse($container->offsetExists('tempKey'));
        $this->assertNull($container->offsetGet('tempKey'));
    }

    /**
     * @throws ContainerException
     */
    public function test_offsetSetWithClosure(): void
    {
        $container = new Container();
        $container->offsetSet('testClosure', fn () => 'boundValue');

        $this->assertSame('boundValue', $container->offsetGet('testClosure'));
    }

    /**
     * @throws ContainerException
     */
    public function test_keyToStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Key must be a scalar value.');

        $container = new Container();

        $container->offsetSet(['nonScalar'], 'value');
    }
}
