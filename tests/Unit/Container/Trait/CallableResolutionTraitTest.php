<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;
use TypeError;

#[CoversClass(Container::class)]
final class CallableResolutionTraitTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_callThrowsExceptionForInvalidArrayCallable(): void
    {
        $container = new CallableResolutionTestableContainer();
        $object = new class() {
            public function method(): void
            {
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Callable resolution error: method name must be a string.");

        $container->testCall([$object, 123]);
    }

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

        // Because PHP enforces the callable type, passing an array with a non‑string method
        // throws a TypeError before container logic is reached.
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
     * @throws Throwable
     */
    public function test_callThrowsContainerExceptionForNonStringMethodNameViaTestableContainer(): void
    {
        $container = new CallableResolutionTestableContainer();
        $dummy = new class() {
            public function validMethod(): string
            {
                return 'ok';
            }
        };
        // supply an invalid method element.
        $invalidCallable = [$dummy, 123];
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Callable resolution error: method name must be a string.");
        $container->testCall($invalidCallable, []);
    }

    /**
     * @throws Throwable
     */
    public function test_callThrowsContainerExceptionForUnsupportedCallableTypeViaTestableContainer(): void
    {
        $container = new CallableResolutionTestableContainer();
        $invalidCallable = new stdClass();
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Unsupported callable type: stdClass");
        $container->testCall($invalidCallable, []);

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
        $container = new CallableResolutionTestableContainer();

        $this->assertSame('direct', $container->testCall($functionName, ['param' => 'direct']));
    }

    /**
     * @throws ContainerException|Throwable
     */
    public function test_callThrowsExceptionForInvalidClassOrObjectInArrayCallable(): void
    {
        $container = new CallableResolutionTestableContainer();

        // Passing an array callable whose first element is not an object or string (e.g. integer)
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected object or class name for ReflectionMethod.');

        $container->testCall([42, 'foo'], []);
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
    public function test_callWithNonObjectInstance(): void
    {
        $dummyClass = new class() {
            public static function foo(): string
            {
                return 'bar';
            }
        };
        $container = new class() extends Container {
            public function make(string $abstract, array $parameters = []): string
            {
                // Force a non-object return.
                return 'notAnObject';
            }
        };
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Callable resolution error: Expected instance to be an object.");

        // For a static callable, passing an array of (class-string, method) is allowed.
        $container->call([get_class($dummyClass), 'foo']);
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

class CallableResolutionTestableContainer extends Container
{
    /**
     * @param callable $callable
     * @param array<string, mixed> $parameters
     * @throws ContainerException|Throwable
     */
    public function testCall(
        mixed $callable,
        array $parameters = []
    ): mixed {
        return $this->doCall($callable, $parameters);
    }
}
