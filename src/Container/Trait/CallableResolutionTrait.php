<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

/**
 * Trait CallableResolutionTrait
 *
 * Allows the container to "call" arbitrary callables (closures, arrays, etc.)
 * with automatic injection of dependencies.
 */
trait CallableResolutionTrait
{
    /**
     * Call a callable with resolved dependencies.
     *
     * Supports invokable objects, array callables, and closures.
     *
     * @param callable $callable
     * @param array<string, mixed> $parameters
     * @throws Throwable
     * @return mixed
     */
    public function call(
        callable $callable,
        array $parameters = []
    ): mixed {
        return $this->doCall($callable, $parameters);
    }

    /**
     * @param callable $callable
     * @param array<string, mixed> $parameters
     * @throws Throwable|ContainerException|NotFoundException|ReflectionException
     */
    protected function doCall(
        mixed $callable,
        array $parameters = []
    ): mixed {
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            $refMethod = new ReflectionMethod($callable, '__invoke');
            $deps = [];
            foreach ($refMethod->getParameters() as $param) {
                $deps[] = $this->resolveParameter($param, $parameters, $refMethod->getDeclaringClass()->getName());
            }

            return $refMethod->invokeArgs((object) $callable, $deps);
        }
        if (is_array($callable) && count($callable) === 2) {
            [$classOrObject, $method] = $callable;
            if (!is_string($method)) {
                throw new ContainerException("Callable resolution error: method name must be a string.");
            }
            if (is_string($classOrObject) || is_object($classOrObject)) {
                $refMethod = new ReflectionMethod($classOrObject, $method);
            } else {
                throw new InvalidArgumentException('Expected object or class name for ReflectionMethod.');
            }

            $deps = [];
            foreach ($refMethod->getParameters() as $param) {
                $deps[] = $this->resolveParameter($param, $parameters, $refMethod->getDeclaringClass()->getName());
            }
            $instance = is_object($classOrObject) ? $classOrObject : $this->make((string) $classOrObject);
            if (!is_object($instance)) {
                throw new ContainerException("Callable resolution error: Expected instance to be an object.");
            }

            return $refMethod->invokeArgs($instance, $deps);
        }
        if (is_string($callable) || $callable instanceof Closure) {
            $refFunc = new ReflectionFunction($callable);
            $deps = [];
            foreach ($refFunc->getParameters() as $param) {
                $deps[] = $this->resolveParameter($param, $parameters, 'ClosureOrFunction');
            }

            return $refFunc->invokeArgs($deps);
        }
        throw new ContainerException("Unsupported callable type: " . get_debug_type($callable));
    }
}
