<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

/**
 * Trait DebuggingTrait
 *
 * Provides a method to generate a rudimentary dependency graph of the container's bindings.
 */
trait DebuggingTrait
{
    /**
     * Generate a dependency graph for debugging.
     *
     * @throws ReflectionException
     * @return array<string, list<string>>
     */
    public function generateDependencyGraph(): array
    {
        $graph = [];
        foreach ($this->bindings as $abstract => $details) {
            $dependencies = [];
            // Ensure the closure is invoked with two parameters: the container and an empty array
            $instance = $details['concrete']($this, []);
            if (is_object($instance)) {
                $reflector = new ReflectionClass($instance);
            } elseif (is_string($instance)) {
                if (!class_exists($instance)) {
                    throw new InvalidArgumentException("Class $instance does not exist.");
                }
                $reflector = new ReflectionClass($instance);
            } else {
                throw new InvalidArgumentException('Expected a valid class name or object for ReflectionClass.');
            }

            if ($constructor = $reflector->getConstructor()) {
                foreach ($constructor->getParameters() as $param) {
                    $type = $param->getType();
                    $dependencies[] = $type ? (string) $type : 'untyped';
                }
            }
            $graph[$abstract] = $dependencies;
        }

        return $graph;
    }
}
