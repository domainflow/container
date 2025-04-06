<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Tests\Unit\Dummy\DummyCircularResolver;
use DomainFlow\Tests\Unit\Dummy\DummyClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class CircularDependencyResolverTraitTest extends TestCase
{
    private DummyCircularResolver $dummy;

    protected function setUp(): void
    {
        $this->dummy = new DummyCircularResolver();
    }

    public function test_resolveCircularDependency_returns_proxy_instance(): void
    {
        $proxy = $this->dummy->publicResolveCircularDependency(DummyClass::class, []);

        $this->assertInstanceOf(DummyClass::class, $proxy);
    }

    /**
     * @throws ContainerException
     */
    public function test_proxy_lazy_resolves_real_instance(): void
    {
        $proxy = $this->dummy->publicResolveCircularDependency(DummyClass::class, []);

        $realInstance = method_exists($proxy, 'getInstance') ? $proxy->getInstance() : $proxy;
        $this->assertInstanceOf(DummyClass::class, $realInstance);
    }

    public function test_multiple_resolutions_do_not_interfere(): void
    {
        $proxy1 = $this->dummy->publicResolveCircularDependency(DummyClass::class, []);
        $proxy2 = $this->dummy->publicResolveCircularDependency(DummyClass::class, []);

        $this->assertNotSame($proxy1, $proxy2);
    }
}
