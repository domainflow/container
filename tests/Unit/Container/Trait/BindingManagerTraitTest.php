<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Tests\Unit\Dummy\DummyBindingManagerB;
use DomainFlow\Tests\Unit\Dummy\DummyClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;
use Throwable;

#[CoversClass(Container::class)]
final class BindingManagerTraitTest extends TestCase
{
    private DummyBindingManagerB $dummy;

    protected function setUp(): void
    {
        $this->dummy = new DummyBindingManagerB();
    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_bind_with_null_concrete_uses_abstract_as_concrete(): void
    {
        $abstract = DummyClass::class;
        $this->dummy->bind($abstract);

        $this->assertTrue(
            $this->dummy->has($abstract),
            "Container should now have '$abstract' bound."
        );

        $obj = $this->dummy->make($abstract);

        $this->assertInstanceOf(DummyClass::class, $obj);
    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_bind_with_closure(): void
    {
        $abstract = 'foo';
        $closure = function () {
            return 'bar';
        };
        $this->dummy->bind($abstract, $closure, false);

        $refProp = new ReflectionProperty($this->dummy, 'bindings');
        $bindingsValue = $refProp->getValue($this->dummy);

        $this->assertArrayHasKey($abstract, $bindingsValue);
        $this->assertSame($closure, $bindingsValue[$abstract]['concrete']);
        $this->assertFalse($bindingsValue[$abstract]['shared']);

        $this->assertTrue($this->dummy->has($abstract));
        $this->assertSame('bar', $this->dummy->make($abstract));
    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_bind_with_string_concrete_valid_class(): void
    {
        $abstract = 'dummy';
        $className = DummyClass::class;

        $this->dummy->bind(
            $abstract,
            $className
        );

        $obj = $this->dummy->make($abstract);
        $this->assertInstanceOf(DummyClass::class, $obj);
    }

    public function test_bind_with_string_concrete_invalid_class_throws_exception(): void
    {
        $this->expectException(ContainerException::class);

        $this->dummy->bind(
            'invalid',
            'NonExistentClass',
        );
    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_singleton_calls_bind_with_shared_true(): void
    {
        $abstract = 'singletonTest';
        $this->dummy->singleton($abstract, DummyClass::class);
        $obj1 = $this->dummy->make($abstract);
        $obj2 = $this->dummy->make($abstract);

        $this->assertSame($obj1, $obj2, 'Singleton calls should return the same instance');
    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_instance_sets_instances(): void
    {
        $abstract = 'instanceTest';
        $dummyObj = new DummyClass();
        $this->dummy->instance($abstract, $dummyObj);

        $this->assertSame($dummyObj, $this->dummy->make($abstract));

    }

    /**
     * @throws ReflectionException|ContainerException|Throwable
     */
    public function test_alias_sets_aliases(): void
    {
        $abstract = 'aliasTarget';
        $alias = 'aliasName';

        $this->dummy->alias($abstract, $alias);
        $this->dummy->bind($abstract, DummyClass::class);

        $objFromAlias = (object) $this->dummy->make($alias);
        $objFromAbstract = (object) $this->dummy->make($abstract);

        $this->assertInstanceOf(DummyClass::class, $objFromAlias);
        $this->assertSame(get_class($objFromAlias), get_class($objFromAbstract));
    }
}
