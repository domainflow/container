<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container\Attribute\Inject;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

/**
 * Trait ReflectionBuilderTrait
 *
 * Contains methods that use reflection to build objects and resolve dependencies.
 */
trait ReflectionBuilderTrait
{
    /**
     * Priority rules for resolving union types.
     *
     * Format: [ "ParentClass::$paramName" => ["Type1", "Type2", ...] ]
     *
     * @var array<string, array<int, string>>
     */
    protected array $unionTypePriority = [];

    /**
     * Build a concrete instance using reflection-based autowiring.
     *
     * @param class-string $concrete
     * @param array<string, mixed> $parameters
     * @throws ContainerException|ReflectionException|NotFoundException|Throwable
     * @return mixed
     */
    public function build(
        string $concrete,
        array $parameters = []
    ): mixed {
        $constructor = null;
        try {
            if (!isset($this->reflectionCache[$concrete])) {
                $this->reflectionCache[$concrete] = new ReflectionClass($concrete);
            }
            $reflector = $this->reflectionCache[$concrete];

            if (isset($parameters['__simulate_reflection_exception'])) {
                throw new ReflectionException("Simulated exception");
            }

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Cannot instantiate [$concrete].");
            }

            $constructor = $reflector->getConstructor();
            if ($constructor === null) {
                $instance = new $concrete();
                $this->injectProperties($instance);

                // Execute after-resolve hooks.
                foreach ($this->afterResolveHooks as $hook) {
                    $resolved = $hook($instance, $concrete, $parameters);
                    if ($resolved !== null) {
                        $instance = $resolved;
                    }
                }

                return $instance;
            }

            // Execute before-resolve hooks.
            foreach ($this->beforeResolveHooks as $hook) {
                $hook($concrete, $parameters);
            }

            $dependencies = [];
            foreach ($constructor->getParameters() as $param) {
                $value = $this->resolveParameter($param, $parameters, $concrete);
                if ($param->isVariadic()) {
                    // Merge each element if variadic.
                    foreach ((array) $value as $v) {
                        $dependencies[] = $v;
                    }
                } else {
                    $dependencies[] = $value;
                }
            }

            $instance = $reflector->newInstanceArgs($dependencies);
            $this->injectProperties($instance);

            // Execute after-resolve hooks.
            foreach ($this->afterResolveHooks as $hook) {
                $resolved = $hook($instance, $concrete, $parameters);
                if ($resolved !== null) {
                    $instance = $resolved;
                }
            }

            return $instance;
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to build [$concrete] (constructor: "
                . ($constructor ? $constructor->getDeclaringClass()->getName() . '::' . $constructor->getName() : 'none')
                . "): " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Resolve a single parameter.
     *
     * @param ReflectionParameter $param
     * @param array<string, mixed> $parameters
     * @param string $parentClass
     * @throws ContainerException|ReflectionException|NotFoundException|Throwable
     * @return mixed
     */
    protected function resolveParameter(
        ReflectionParameter $param,
        array $parameters,
        string $parentClass
    ): mixed {
        $paramName = $param->getName();

        if ($param->isVariadic()) {
            return array_key_exists($paramName, $parameters)
                ? (is_array($parameters[$paramName]) ? $parameters[$paramName] : [$parameters[$paramName]])
                : [];
        }

        if (array_key_exists($paramName, $parameters)) {
            return $parameters[$paramName];
        }

        $type = $param->getType();
        if ($type === null) {
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            throw new ContainerException(
                "Cannot resolve untyped parameter [\$$paramName] in [$parentClass]."
            );
        }

        return $this->resolveType(
            $type,
            $param,
            $parentClass
        );
    }

    /**
     * Dispatch type resolution.
     *
     * @param ReflectionType $type
     * @param ReflectionParameter $param
     * @param string $parentClass
     * @throws ContainerException|ReflectionException|NotFoundException|Throwable
     * @return mixed
     */
    protected function resolveType(
        ReflectionType $type,
        ReflectionParameter $param,
        string $parentClass
    ): mixed {
        if ($type instanceof ReflectionNamedType) {
            return $this->resolveNamedType($type, $param, $parentClass);
        }
        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionType($type, $param, $parentClass);
        }
        if ($type instanceof ReflectionIntersectionType) {
            return $this->resolveIntersectionType($type, $param, $parentClass);
        }
        $paramName = $param->getName();
        throw new ContainerException(
            "Unable to resolve union-typed parameter [\$$paramName] in [$parentClass]. Tried types: " . "."
        );
    }

    /**
     * Resolve a named type.
     *
     * @param ReflectionNamedType $type
     * @param ReflectionParameter $param
     * @param string $parentClass
     * @throws ContainerException|NotFoundException|ReflectionException|Throwable
     * @return mixed
     */
    protected function resolveNamedType(
        ReflectionNamedType $type,
        ReflectionParameter $param,
        string $parentClass
    ): mixed {
        if ($type->isBuiltin()) {
            if ($param->isOptional()) {
                return $param->getDefaultValue();
            }
            throw new ContainerException("Cannot resolve built-in parameter [\${$param->getName()}] in [$parentClass].");
        }

        $dependencyName = $type->getName();
        $contextual = $this->contextual[$parentClass] ?? [];

        if (isset($contextual[$dependencyName])) {
            return $this->make($contextual[$dependencyName]);
        }

        if ($this->has($dependencyName)) {
            return $this->make($dependencyName);
        }

        if ($param->isOptional()) {
            return $param->getDefaultValue();
        }

        throw new NotFoundException(
            "No entry or class found for parameter [\${$param->getName()}] in [$parentClass] (type: $dependencyName)."
        );
    }

    /**
     * Resolve a union type by attempting each option.
     *
     * @param ReflectionUnionType $type
     * @param ReflectionParameter $param
     * @param string $parentClass
     * @throws ContainerException|ReflectionException|Throwable
     * @return mixed
     */
    protected function resolveUnionType(
        ReflectionUnionType $type,
        ReflectionParameter $param,
        string $parentClass
    ): mixed {
        // If the parameter is optional, use its default value.
        if ($param->isOptional()) {
            return $param->getDefaultValue();
        }

        $paramName = $param->getName();
        $contextual = $this->contextual[$parentClass] ?? [];
        $triedTypes = [];
        $namedTypes = $type->getTypes();
        $priorityKey = $parentClass . '::$' . $paramName;

        // If a priority list is set, sort the types accordingly.
        if (isset($this->unionTypePriority[$priorityKey])) {
            $priorityList = $this->unionTypePriority[$priorityKey];
            usort($namedTypes, function ($a, $b) use ($priorityList) {
                $nameA = ($a instanceof ReflectionNamedType) ? $a->getName() : '';
                $nameB = ($b instanceof ReflectionNamedType) ? $b->getName() : '';
                $indexA = array_search($nameA, $priorityList, true);
                $indexB = array_search($nameB, $priorityList, true);
                $indexA = ($indexA === false) ? PHP_INT_MAX : $indexA;
                $indexB = ($indexB === false) ? PHP_INT_MAX : $indexB;

                return $indexA <=> $indexB;
            });
            // Return the first resolvable candidate based on the sorted order.
            foreach ($namedTypes as $namedType) {
                if ($namedType instanceof ReflectionNamedType && !$namedType->isBuiltin()) {
                    $depName = $namedType->getName();
                    $triedTypes[] = $depName;
                    if (isset($contextual[$depName])) {
                        return $this->make($contextual[$depName]);
                    }
                    if ($this->has($depName)) {
                        return $this->make($depName);
                    }
                }
            }
            throw new ContainerException(
                "Unable to resolve union-typed parameter [\$$paramName] in [$parentClass]. Tried types: " . implode(', ', $triedTypes) . "."
            );
        }

        // Without a priority list, gather all resolvable candidate names.
        $resolvableCandidates = [];
        foreach ($namedTypes as $namedType) {
            if ($namedType instanceof ReflectionNamedType && !$namedType->isBuiltin()) {
                $depName = $namedType->getName();
                $triedTypes[] = $depName;
                if (isset($contextual[$depName])) {
                    return $this->make($contextual[$depName]);
                }
                if ($this->has($depName)) {
                    $resolvableCandidates[] = $depName;
                }
            }
        }
        if (count($resolvableCandidates) === 1) {
            return $this->make($resolvableCandidates[0]);
        }
        if (count($resolvableCandidates) > 1) {
            throw new ContainerException(
                "Ambiguous union-typed parameter [\$$paramName] in [$parentClass]. Candidates: " . implode(', ', $resolvableCandidates) . "."
            );
        }
        throw new ContainerException(
            "Unable to resolve union-typed parameter [\$$paramName] in [$parentClass]. Tried types: " . implode(', ', $triedTypes) . "."
        );
    }

    /**
     * Resolve an intersection type.
     *
     * @param ReflectionIntersectionType $type
     * @param ReflectionParameter $param
     * @param string $parentClass
     * @throws ContainerException|ReflectionException|Throwable
     * @return mixed
     */
    protected function resolveIntersectionType(
        ReflectionIntersectionType $type,
        ReflectionParameter $param,
        string $parentClass
    ): mixed {
        $paramName = $param->getName();
        /** @var list<ReflectionNamedType> $subTypes */
        $subTypes = array_filter(
            $type->getTypes(),
            fn ($t) => $t instanceof ReflectionNamedType
        );
        $classType = null;
        $interfaceTypes = [];
        foreach ($subTypes as $t) {
            if ($t->isBuiltin()) {
                continue;
            }
            $name = $t->getName();
            if (class_exists($name)) {
                $classType = $name;
            } else {
                $interfaceTypes[] = $name;
            }
        }
        if ($classType !== null) {
            $instance = $this->make($classType);
            if (!is_object($instance)) {
                throw new ContainerException("Resolved dependency for [$classType] is not an object.");
            }
            $unsatisfied = [];
            foreach ($interfaceTypes as $iface) {
                if (!is_a($instance, $iface)) {
                    $unsatisfied[] = $iface;
                }
            }
            if ($unsatisfied) {
                throw new ContainerException("Instance of class [$classType] does not satisfy intersection type. Missing: " . implode(', ', $unsatisfied) . ".");
            }

            return $instance;
        }
        if ($param->isOptional()) {
            return $param->getDefaultValue();
        }
        throw new ContainerException(
            "Unable to handle intersection-typed parameter [\$$paramName] in [$parentClass]. Required types: "
            . implode(' & ', array_map(fn (ReflectionNamedType $t) => $t->getName(), $subTypes)) . "."
        );
    }

    /**
     * Inject properties marked with the #[Inject] attribute.
     *
     * @param object $instance
     * @throws ContainerException|ReflectionException|Throwable
     * @return void
     */
    public function injectProperties(
        object $instance
    ): void {
        $refClass = new ReflectionClass($instance);
        foreach ($refClass->getProperties() as $property) {
            $attributes = $property->getAttributes(Inject::class);
            if (!empty($attributes)) {
                $type = $property->getType();
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $dependency = $this->make($type->getName());
                    if ($refClass->isFinal() && $property->isPrivate()) {
                        $propertyName = $property->getName();
                        $setter = function ($value) use ($propertyName) {
                            $this->{$propertyName} = $value;
                        };
                        $boundSetter = Closure::bind($setter, $instance, $refClass->getName());
                        $boundSetter($dependency);
                    } else {
                        $original = $property->isPublic();
                        $this->discard(fn () => $property->setAccessible(true));
                        $property->setValue($instance, $dependency);
                        if (!$original) {
                            $this->discard(fn () => $property->setAccessible(false));
                        }
                    }
                }
            }
        }
    }

    /**
     * Discard the result of a callable.
     *
     * @param callable(): void $callable
     * @return void
     */
    private function discard(
        callable $callable
    ): void {
        $callable();
    }

    /**
     * Set priority rules for resolving a union type.
     *
     * @param string $key
     * @param array<int, string> $priorityList
     * @return void
     */
    public function setUnionTypePriority(
        string $key,
        array $priorityList
    ): void {
        $this->unionTypePriority[$key] = $priorityList;
    }
}
