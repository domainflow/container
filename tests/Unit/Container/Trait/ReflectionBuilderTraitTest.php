<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use DomainFlow\Tests\Unit\Dummy\AbstractDummy;
use DomainFlow\Tests\Unit\Dummy\DummyAfterResolveNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyAfterResolveNoConstructorReplacement;
use DomainFlow\Tests\Unit\Dummy\DummyAlternateNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyBindingManagerB;
use DomainFlow\Tests\Unit\Dummy\DummyBuiltin;
use DomainFlow\Tests\Unit\Dummy\DummyContextual;
use DomainFlow\Tests\Unit\Dummy\DummyContextualNamed;
use DomainFlow\Tests\Unit\Dummy\DummyFinalPrivateInject;
use DomainFlow\Tests\Unit\Dummy\DummyInject;
use DomainFlow\Tests\Unit\Dummy\DummyInterfaceA;
use DomainFlow\Tests\Unit\Dummy\DummyNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyNotFound;
use DomainFlow\Tests\Unit\Dummy\DummyOptionalInterfaceDep;
use DomainFlow\Tests\Unit\Dummy\DummyOptionalUntyped;
use DomainFlow\Tests\Unit\Dummy\DummyProtectedInject;
use DomainFlow\Tests\Unit\Dummy\DummyUnionA;
use DomainFlow\Tests\Unit\Dummy\DummyUnionB;
use DomainFlow\Tests\Unit\Dummy\DummyUnresolvable1;
use DomainFlow\Tests\Unit\Dummy\DummyUnresolvable2;
use DomainFlow\Tests\Unit\Dummy\DummyUntyped;
use DomainFlow\Tests\Unit\Dummy\DummyVariadicConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithIntersectionConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithIntersectionConstructorFail;
use DomainFlow\Tests\Unit\Dummy\DummyWithOptionalUnion;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionConstructor2;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionContextual;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnresolvableUnion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

#[CoversClass(Container::class)]
final class ReflectionBuilderTraitTest extends TestCase
{
    private DummyBindingManagerB $container;

    protected function setUp(): void
    {
        $this->container = new DummyBindingManagerB();
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_without_constructor(): void
    {
        $instance = $this->container->build(DummyNoConstructor::class);
        $this->assertInstanceOf(DummyNoConstructor::class, $instance);
        $this->assertSame('bar', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_constructor(): void
    {
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'hello']);
        $this->assertInstanceOf(DummyWithConstructor::class, $instance);
        $this->assertSame('hello', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_inject_properties(): void
    {
        $instance = $this->container->build(DummyInject::class);
        $this->assertInstanceOf(DummyInject::class, $instance);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_union_constructor(): void
    {
        $instance = $this->container->build(DummyWithUnionConstructor::class);
        $this->assertInstanceOf(DummyWithUnionConstructor::class, $instance);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_intersection_constructor(): void
    {
        $instance = $this->container->build(DummyWithIntersectionConstructor::class);
        $this->assertInstanceOf(DummyWithIntersectionConstructor::class, $instance);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_non_instantiable_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->build(AbstractDummy::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_before_and_after_hooks_modify_instance(): void
    {
        $this->container->addBeforeResolve(function (string $concrete, array $parameters): void {
            // For testing, we do nothing here.
        });
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
            if (property_exists($instance, 'foo')) {
                $instance->foo = 'modified';
            }

            return null;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'initial']);
        $this->assertSame('modified', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_without_constructor_afterResolveHook_replaces_instance(): void
    {
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
            if ($instance instanceof DummyAfterResolveNoConstructor) {
                return new DummyAfterResolveNoConstructorReplacement();
            }

            return null;
        });

        $instance = $this->container->build(DummyAfterResolveNoConstructor::class);
        $this->assertInstanceOf(DummyAfterResolveNoConstructorReplacement::class, $instance);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_constructor_beforeHook_called(): void
    {
        $called = false;
        $this->container->addBeforeResolve(function (string $concrete, array $parameters) use (&$called): void {
            $called = true;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'test']);
        $this->assertTrue($called);
        $this->assertSame('test', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_constructor_afterHook_replaces_instance(): void
    {
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
            if ($instance instanceof DummyWithConstructor) {
                return new DummyWithConstructor('hooked');
            }

            return null;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'original']);
        $this->assertSame('hooked', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveParameter_variadic_returns_empty_array(): void
    {
        $instance = $this->container->build(DummyVariadicConstructor::class);
        $this->assertIsArray($instance->values);
        $this->assertEmpty($instance->values);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveParameter_untyped_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->build(DummyUntyped::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveNamedType_builtin_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->build(DummyBuiltin::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveNamedType_contextual_binding(): void
    {
        $this->container->addContextualBinding(
            DummyContextual::class,
            DummyNoConstructor::class,
            DummyAlternateNoConstructor::class
        );
        $instance = $this->container->build(DummyContextual::class);
        $this->assertInstanceOf(DummyAlternateNoConstructor::class, $instance->dep);
        $this->assertSame('alternate', $instance->dep->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_priority_preference(): void
    {
        $this->container->bind(DummyUnionB::class, function () {
            return new DummyUnionB();
        });

        $priorityKey = DummyWithUnionConstructor2::class . '::$dependency';
        $this->container->setUnionTypePriority($priorityKey, [DummyUnionB::class]);
        $instance = $this->container->build(DummyWithUnionConstructor2::class);
        $this->assertInstanceOf(DummyUnionB::class, $instance->dependency);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_optional_returns_default(): void
    {
        $instance = $this->container->build(DummyWithOptionalUnion::class);
        $this->assertNull($instance->dependency);

    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_unresolvable_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->build(DummyWithUnresolvableUnion::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveIntersectionType_unsatisfied_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->container->build(DummyWithIntersectionConstructorFail::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_injectProperties_final_private_injection(): void
    {
        $instance = $this->container->build(DummyFinalPrivateInject::class);
        $this->assertInstanceOf(DummyNoConstructor::class, $instance->getDependency());
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_variadic_parameters_non_empty(): void
    {
        $instance = $this->container->build(DummyVariadicConstructor::class, ['values' => 'testValue']);
        $this->assertIsArray($instance->values);
        $this->assertSame(['testValue'], $instance->values);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_fails_with_reflection_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Failed to build");
        $this->container->build(DummyVariadicConstructor::class, ['__simulate_reflection_exception' => true]);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveParameter_optional_untyped_returns_default(): void
    {
        $instance = $this->container->build(DummyOptionalUntyped::class);
        $this->assertSame('defaultUntyped', $instance->param);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveType_with_invalid_type_throws_exception(): void
    {
        $fakeType = $this->createMock(ReflectionType::class);
        $mockParam = $this->createMock(ReflectionParameter::class);
        $mockParam->method('getType')->willReturn($fakeType);
        $mockParam->method('getName')->willReturn('fakeParam');

        $this->expectException(ContainerException::class);
        $method = new ReflectionMethod($this->container, 'resolveType');

        $method->invoke($this->container, $fakeType, $mockParam, 'DummyParent', []);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveNamedType_contextual_binding_correct(): void
    {
        $this->container->addContextualBinding(
            DummyContextualNamed::class,
            DummyNoConstructor::class,
            DummyAlternateNoConstructor::class
        );
        $instance = $this->container->build(DummyContextualNamed::class);
        $this->assertInstanceOf(DummyAlternateNoConstructor::class, $instance->dep);
        $this->assertSame('alternate', $instance->dep->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveNamedType_not_found_throws_exception(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->build(DummyNotFound::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_contextual_priority(): void
    {
        $priorityKey = DummyWithUnionContextual::class . '::$dependency';
        $this->container->setUnionTypePriority($priorityKey, [DummyUnionA::class]);
        $this->container->addContextualBinding(
            DummyWithUnionContextual::class,
            DummyUnionA::class,
            DummyUnionB::class
        );
        $instance = $this->container->build(DummyWithUnionContextual::class);
        $this->assertInstanceOf(DummyUnionB::class, $instance->dependency);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_priority_unresolvable_throws_exception(): void
    {
        $container = new class() extends DummyBindingManagerB {
            public function has(string $id): bool
            {
                if ($id === DummyUnresolvable1::class || $id === DummyUnresolvable2::class) {
                    return false;
                }

                return parent::has($id);
            }
        };
        $priorityKey = DummyWithUnresolvableUnion::class . '::$dependency';
        $container->setUnionTypePriority($priorityKey, [DummyUnresolvable1::class]);
        $this->expectException(ContainerException::class);
        $container->build(DummyWithUnresolvableUnion::class);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveIntersectionType_with_builtin_in_subtypes(): void
    {
        $fakeNamedType = $this->createMock(ReflectionNamedType::class);
        $fakeNamedType->method('isBuiltin')->willReturn(true);

        $validNamedType = $this->createMock(ReflectionNamedType::class);
        $validNamedType->method('isBuiltin')->willReturn(false);
        $validNamedType->method('getName')->willReturn(DummyNoConstructor::class);
        $intersectionType = $this->getMockBuilder(ReflectionIntersectionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $intersectionType->method('getTypes')->willReturn([$fakeNamedType, $validNamedType]);

        $mockParam = $this->createMock(ReflectionParameter::class);
        $mockParam->method('getName')->willReturn('param');
        $mockParam->method('isOptional')->willReturn(false);

        // Pre-cache an instance for DummyNoConstructor.
        $instance = new DummyNoConstructor();
        $this->container->instance(DummyNoConstructor::class, $instance);

        $method = new ReflectionMethod($this->container, 'resolveIntersectionType');

        $result = $method->invoke($this->container, $intersectionType, $mockParam, 'DummyParent');
        $this->assertSame($instance, $result);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveIntersectionType_make_non_object_throws_exception(): void
    {
        $validNamedType = $this->createMock(ReflectionNamedType::class);
        $validNamedType->method('isBuiltin')->willReturn(false);
        $validNamedType->method('getName')->willReturn('NonExistentClass');
        $intersectionType = $this->getMockBuilder(ReflectionIntersectionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $intersectionType->method('getTypes')->willReturn([$validNamedType]);

        $mockParam = $this->createMock(ReflectionParameter::class);
        $mockParam->method('getName')->willReturn('param');
        $mockParam->method('isOptional')->willReturn(false);

        $containerMock = $this->getMockBuilder(DummyBindingManagerB::class)
            ->onlyMethods(['make'])
            ->getMock();
        $containerMock->method('make')->willReturn('notAnObject');

        $method = new ReflectionMethod($containerMock, 'resolveIntersectionType');

        $this->expectException(ContainerException::class);
        $method->invoke($containerMock, $intersectionType, $mockParam, 'DummyParent');
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveIntersectionType_unsatisfied_interfaces_throws_exception(): void
    {
        $classType = $this->createMock(ReflectionNamedType::class);
        $classType->method('isBuiltin')->willReturn(false);
        $classType->method('getName')->willReturn(DummyNoConstructor::class);

        $interfaceType = $this->createMock(ReflectionNamedType::class);
        $interfaceType->method('isBuiltin')->willReturn(false);
        $interfaceType->method('getName')->willReturn(DummyInterfaceA::class);

        $intersectionType = $this->getMockBuilder(ReflectionIntersectionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $intersectionType->method('getTypes')->willReturn([$classType, $interfaceType]);

        $mockParam = $this->createMock(ReflectionParameter::class);
        $mockParam->method('getName')->willReturn('param');
        $mockParam->method('isOptional')->willReturn(false);

        $instance = new DummyNoConstructor();
        $this->container->instance(DummyNoConstructor::class, $instance);

        $method = new ReflectionMethod($this->container, 'resolveIntersectionType');

        $this->expectException(ContainerException::class);
        $method->invoke($this->container, $intersectionType, $mockParam, 'DummyParent');
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveIntersectionType_optional_returns_default(): void
    {
        $intersectionType = $this->getMockBuilder(ReflectionIntersectionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $intersectionType->method('getTypes')->willReturn([]);

        $mockParam = $this->createMock(ReflectionParameter::class);
        $mockParam->method('getName')->willReturn('param');
        $mockParam->method('isOptional')->willReturn(true);
        $mockParam->method('getDefaultValue')->willReturn('defaultIntersection');

        $method = new ReflectionMethod($this->container, 'resolveIntersectionType');

        $result = $method->invoke($this->container, $intersectionType, $mockParam, 'DummyParent');
        $this->assertSame('defaultIntersection', $result);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_injectProperties_protected_property(): void
    {
        $instance = $this->container->build(DummyProtectedInject::class);
        $this->assertInstanceOf(DummyNoConstructor::class, $instance->getDependency());
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveNamedType_optional_returns_default_non_builtin(): void
    {
        $instance = $this->container->build(DummyOptionalInterfaceDep::class);
        $this->assertNull($instance->dep);
    }

    /**
     * @throws Throwable|ReflectionException
     */
    public function test_resolveUnionType_contextual_binding_direct(): void
    {
        $fakeNamedType = $this->createMock(ReflectionNamedType::class);
        $fakeNamedType->method('isBuiltin')->willReturn(false);
        $fakeNamedType->method('getName')->willReturn(DummyUnionA::class);

        $fakeUnionType = $this->getMockBuilder(ReflectionUnionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeUnionType->method('getTypes')->willReturn([$fakeNamedType]);

        $fakeParam = $this->createMock(ReflectionParameter::class);
        $fakeParam->method('getName')->willReturn('dependency');
        $fakeParam->method('isOptional')->willReturn(false);

        $parentClass = DummyWithUnionContextual::class;
        $this->container->addContextualBinding(
            $parentClass,
            DummyUnionA::class,
            DummyUnionB::class
        );

        $method = new ReflectionMethod($this->container, 'resolveUnionType');

        $result = $method->invoke($this->container, $fakeUnionType, $fakeParam, $parentClass);

        $this->assertInstanceOf(DummyUnionB::class, $result);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveUnionType_no_resolvable_candidates_throws_exception(): void
    {
        $fakeNamedTypeA = $this->createMock(ReflectionNamedType::class);
        $fakeNamedTypeA->method('isBuiltin')->willReturn(false);
        $fakeNamedTypeA->method('getName')->willReturn(DummyUnionA::class);

        $fakeNamedTypeB = $this->createMock(ReflectionNamedType::class);
        $fakeNamedTypeB->method('isBuiltin')->willReturn(false);
        $fakeNamedTypeB->method('getName')->willReturn(DummyUnionB::class);

        $fakeUnionType = $this->getMockBuilder(ReflectionUnionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeUnionType->method('getTypes')->willReturn([$fakeNamedTypeA, $fakeNamedTypeB]);

        $fakeParam = $this->createMock(ReflectionParameter::class);
        $fakeParam->method('getName')->willReturn('dependency');
        $fakeParam->method('isOptional')->willReturn(false);

        $container = new class() extends DummyBindingManagerB {
            public function has(string $id): bool
            {
                return false;
            }
        };

        $parentClass = DummyWithUnionContextual::class;

        $method = new ReflectionMethod($container, 'resolveUnionType');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Unable to resolve union-typed parameter [\$dependency] in [$parentClass]");

        $method->invoke($container, $fakeUnionType, $fakeParam, $parentClass);
    }

    /**
     * @throws Exception|ReflectionException
     */
    public function test_resolveIntersectionType_throws_if_make_returns_non_object(): void
    {
        $validClassType = $this->createMock(ReflectionNamedType::class);
        $validClassType->method('isBuiltin')->willReturn(false);
        $validClassType->method('getName')->willReturn(DummyNoConstructor::class);

        $interfaceType = $this->createMock(ReflectionNamedType::class);
        $interfaceType->method('isBuiltin')->willReturn(false);
        $interfaceType->method('getName')->willReturn(DummyInterfaceA::class);

        $fakeIntersectionType = $this->getMockBuilder(ReflectionIntersectionType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $fakeIntersectionType->method('getTypes')->willReturn([$validClassType, $interfaceType]);

        $fakeParam = $this->createMock(ReflectionParameter::class);
        $fakeParam->method('getName')->willReturn('dependency');
        $fakeParam->method('isOptional')->willReturn(false);

        $containerMock = $this->getMockBuilder(DummyBindingManagerB::class)
            ->onlyMethods(['make'])
            ->getMock();
        $containerMock->method('make')->willReturn('invalid_non_object');

        $method = new ReflectionMethod($containerMock, 'resolveIntersectionType');

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Resolved dependency for [DomainFlow\Tests\Unit\Dummy\DummyNoConstructor] is not an object.");

        $method->invoke($containerMock, $fakeIntersectionType, $fakeParam, 'DummyParent');
    }

    /**
     * @throws ReflectionException
     */
    public function test_setUnionTypePriority_stores_priority_list(): void
    {
        $priorityList = ['TypeA', 'TypeB', 'TypeC'];
        $this->container->setUnionTypePriority('SomeClass::$param', $priorityList);

        $refProperty = new ReflectionProperty($this->container, 'unionTypePriority');

        $storedPriorities = $refProperty->getValue($this->container);

        $this->assertArrayHasKey('SomeClass::$param', $storedPriorities);
        $this->assertSame($priorityList, $storedPriorities['SomeClass::$param']);
    }

}
