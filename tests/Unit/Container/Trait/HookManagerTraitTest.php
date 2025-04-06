<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Tests\Unit\Dummy\DummyHookManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionProperty;

#[CoversClass(Container::class)]
final class HookManagerTraitTest extends TestCase
{
    private DummyHookManager $dummy;

    protected function setUp(): void
    {
        $this->dummy = new DummyHookManager();
    }

    /**
     * @throws ReflectionException
     */
    public function test_addBeforeResolve_adds_hook(): void
    {
        $hook = function (string $concrete, array $params): void {
            // Dummy hook for testing.
        };

        $this->dummy->addBeforeResolve($hook);

        $refProperty = new ReflectionProperty($this->dummy, 'beforeResolveHooks');

        $hooks = $refProperty->getValue($this->dummy);

        $this->assertIsArray($hooks);
        $this->assertCount(1, $hooks);
        $this->assertSame($hook, $hooks[0]);
    }

    /**
     * @throws ReflectionException
     */
    public function test_addAfterResolve_adds_hook(): void
    {
        $hook = function (object $instance, string $concrete, array $params): ?object {
            return null;
        };

        $this->dummy->addAfterResolve($hook);

        $refProperty = new ReflectionProperty($this->dummy, 'afterResolveHooks');

        $hooks = $refProperty->getValue($this->dummy);

        $this->assertIsArray($hooks);
        $this->assertCount(1, $hooks);
        $this->assertSame($hook, $hooks[0]);
    }
}
