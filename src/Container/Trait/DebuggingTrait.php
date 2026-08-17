<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

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
     * Inspects declared bindings only; it never invokes a binding's factory,
     * so a diagnostic call cannot open connections, mutate registrations, or
     * run any other consumer side effect. A binding registered with a
     * class-string concrete is introspected directly via reflection. A
     * binding registered with a Closure concrete has no concrete class
     * discoverable without invoking it, so it is reported with kind
     * 'dynamic' and an empty dependency list instead of being executed.
     *
     * @throws ReflectionException
     * @return array<string, array{kind: 'class'|'dynamic', dependencies: list<string>}>
     */
    public function generateDependencyGraph(): array
    {
        $graph = [];
        foreach (array_keys($this->bindings) as $abstract) {
            $graph[$abstract] = isset($this->cacheableBindings[$abstract])
                ? ['kind' => 'class', 'dependencies' => $this->describeClassDependencies($this->cacheableBindings[$abstract]['concrete'])]
                : ['kind' => 'dynamic', 'dependencies' => []];
        }

        return $graph;
    }

    /**
     * @param class-string $className
     * @throws ReflectionException
     * @return list<string>
     */
    private function describeClassDependencies(string $className): array
    {
        $dependencies = [];
        $constructor = (new ReflectionClass($className))->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                $dependencies[] = $type === null ? 'untyped' : $this->describeType($type);
            }
        }

        return $dependencies;
    }

    private function describeType(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn (ReflectionType $nestedType): string => $this->describeType($nestedType),
                $type->getTypes()
            ));
        }

        $intersectionTypes = $type instanceof ReflectionIntersectionType ? $type->getTypes() : [];

        return implode('&', array_map(
            fn (ReflectionType $nestedType): string => $this->describeType($nestedType),
            $intersectionTypes
        ));
    }
}
