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
use DomainFlow\Tests\Unit\Dummy\DummyInterfaceC;
use DomainFlow\Tests\Unit\Dummy\DummyNoConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyNotFound;
use DomainFlow\Tests\Unit\Dummy\DummyOnlyC;
use DomainFlow\Tests\Unit\Dummy\DummyOnlyCAndD;
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
use DomainFlow\Tests\Unit\Dummy\DummyWithIntersectionOfClassAndInterface;
use DomainFlow\Tests\Unit\Dummy\DummyWithIntersectionUnsatisfiedInterface;
use DomainFlow\Tests\Unit\Dummy\DummyWithOptionalIntersection;
use DomainFlow\Tests\Unit\Dummy\DummyWithOptionalUnion;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionConstructor;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionConstructor2;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnionContextual;
use DomainFlow\Tests\Unit\Dummy\DummyWithUnresolvableUnion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
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
    public function test_build_does_not_run_resolution_hooks(): void
    {
        $beforeCalls = 0;
        $afterCalls = 0;
        $this->container->addBeforeResolve(function (string $concrete, array $parameters) use (&$beforeCalls): void {
            $beforeCalls++;
        });
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters) use (&$afterCalls): ?object {
            $afterCalls++;

            return null;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'initial']);
        $this->assertSame('initial', $instance->foo);
        $this->assertSame(0, $beforeCalls);
        $this->assertSame(0, $afterCalls);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_without_constructor_does_not_run_afterResolveHook(): void
    {
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
            if ($instance instanceof DummyAfterResolveNoConstructor) {
                return new DummyAfterResolveNoConstructorReplacement();
            }

            return null;
        });

        $instance = $this->container->build(DummyAfterResolveNoConstructor::class);
        $this->assertInstanceOf(DummyAfterResolveNoConstructor::class, $instance);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_constructor_does_not_run_beforeHook(): void
    {
        $called = false;
        $this->container->addBeforeResolve(function (string $concrete, array $parameters) use (&$called): void {
            $called = true;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'test']);
        $this->assertFalse($called);
        $this->assertSame('test', $instance->foo);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_build_with_constructor_does_not_run_afterHook(): void
    {
        $this->container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
            if ($instance instanceof DummyWithConstructor) {
                return new DummyWithConstructor('hooked');
            }

            return null;
        });
        $instance = $this->container->build(DummyWithConstructor::class, ['foo' => 'original']);
        $this->assertSame('original', $instance->foo);
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
    public function test_resolveUnionType_priority_autowires_class_even_when_has_does_not_advertise_it(): void
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
        $instance = $container->build(DummyWithUnresolvableUnion::class);

        $this->assertInstanceOf(DummyUnresolvable1::class, $instance->dependency);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveIntersectionType_returns_instance_satisfying_all_members(): void
    {
        $instance = $this->container->build(DummyWithIntersectionOfClassAndInterface::class);

        $this->assertInstanceOf(DummyOnlyC::class, $instance->dependency);
        $this->assertInstanceOf(DummyInterfaceC::class, $instance->dependency);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveIntersectionType_make_non_object_throws_exception(): void
    {
        $container = new class() extends DummyBindingManagerB {
            public function make(string $abstract, array $parameters = []): string
            {
                return 'notAnObject';
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            "Resolved dependency for [" . DummyOnlyC::class . "] is not an object."
        );

        $container->build(DummyWithIntersectionOfClassAndInterface::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveIntersectionType_unsatisfied_interfaces_throws_exception(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            "Instance of class [" . DummyNoConstructor::class . "] does not satisfy intersection type. Missing: "
            . DummyInterfaceA::class . "."
        );

        $this->container->build(DummyWithIntersectionUnsatisfiedInterface::class);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveIntersectionType_optional_returns_default(): void
    {
        $instance = $this->container->build(DummyWithOptionalIntersection::class);

        $this->assertInstanceOf(DummyOnlyCAndD::class, $instance->dependency);
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
     * @throws NotFoundException|Throwable|ContainerException|ReflectionException
     */
    public function test_resolveUnionType_contextual_binding_direct(): void
    {
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
    public function test_resolveUnionType_detects_ambiguous_autowireable_candidates(): void
    {
        $container = new class() extends DummyBindingManagerB {
            public function has(string $id): bool
            {
                return false;
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage(
            "Ambiguous union-typed parameter [\$dependency] in [" . DummyWithUnionContextual::class . "]"
        );

        $container->build(DummyWithUnionContextual::class);
    }
}
