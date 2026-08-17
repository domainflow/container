<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;
use TypeError;

#[CoversClass(Container::class)]
final class CallableResolutionTraitTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_callResolvesMethodParameters(): void
    {
        $container = new Container();
        $object = new class() {
            public function method(string $param): string
            {
                return $param;
            }
        };

        $result = $container->call([$object, 'method'], ['param' => 'resolved']);

        $this->assertSame('resolved', $result);
    }

    /**
     * @throws Throwable
     */
    public function test_callThrowsContainerExceptionForNonObjectInstance(): void
    {
        $container = new class() extends Container {
            public function make(string $abstract, array $parameters = []): string
            {
                return 'notAnObject'; // Force the failure branch.
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Callable resolution error: Expected instance to be an object.");

        $dummy = new class() {
            public static function foo(): string
            {
                return 'foo';
            }
        };
        $container->call([get_class($dummy), 'foo']);
    }

    /**
     * @throws Throwable
     */
    public function test_callThrowsTypeErrorForInvalidArrayCallable(): void
    {
        $container = new Container();
        $object = new class() {
            public function method(): void
            {
            }
        };

        // PHP enforces the `callable` type on call()'s parameter, so an array
        // whose method element is not a string throws a TypeError before any
        // container logic runs.
        $this->expectException(TypeError::class);
        $container->call([$object, 123]);
    }

    public function test_callThrowsTypeErrorForNonCallableType(): void
    {
        $container = new Container();
        $this->expectException(TypeError::class);
        $container->call(42); // Integers are not callable, triggers PHP TypeError
    }

    /**
     * @throws ContainerException|Throwable
     */
    public function test_callWithStringCallableDirectly(): void
    {
        $functionName = 'test_function_callable_direct';
        if (!function_exists($functionName)) {
            eval('function test_function_callable_direct($param = "direct"): string { return $param; }');
        }
        $container = new Container();

        $this->assertSame('direct', $container->call($functionName, ['param' => 'direct']));
    }

    /**
     * @throws Throwable
     */
    public function test_callWithInvokableObject(): void
    {
        $container = new Container();
        $invokable = new class() {
            public function __invoke(): string
            {
                return 'invoked';
            }
        };
        $this->assertSame('invoked', $container->call($invokable));
    }

    /**
     * @throws Throwable
     */
    public function test_callWithArrayCallableValid(): void
    {
        $container = new Container();
        $object = new class() {
            public function myMethod(): string
            {
                return 'method called';
            }
        };
        $this->assertSame('method called', $container->call([$object, 'myMethod']));
    }

    /**
     * @throws Throwable
     */
    public function test_callWithClosureUsingParameterResolution(): void
    {
        $container = new Container();
        $closure = function (int $param) {
            return $param;
        };
        $this->assertSame(789, $container->call($closure, ['param' => 789]));
    }

    /**
     * @throws Throwable
     */
    public function test_callWithClosure(): void
    {
        $container = new Container();
        $closure = function () {
            return 'closure executed';
        };
        $this->assertSame('closure executed', $container->call($closure));
    }
}
