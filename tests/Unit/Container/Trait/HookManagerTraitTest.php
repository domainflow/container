<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

#[CoversClass(Container::class)]
final class HookManagerTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_addBeforeResolve_hook_runs_before_make_resolves(): void
    {
        $this->container->bind('TestService', fn () => new stdClass());

        $calls = [];
        $this->container->addBeforeResolve(function (string $concrete, array $params) use (&$calls): void {
            $calls[] = $concrete;
        });

        $this->container->make('TestService');

        $this->assertSame(['TestService'], $calls);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_addBeforeResolve_supports_multiple_hooks_in_registration_order(): void
    {
        $this->container->bind('TestService', fn () => new stdClass());

        $calls = [];
        $this->container->addBeforeResolve(function () use (&$calls): void {
            $calls[] = 'first';
        });
        $this->container->addBeforeResolve(function () use (&$calls): void {
            $calls[] = 'second';
        });

        $this->container->make('TestService');

        $this->assertSame(['first', 'second'], $calls);
    }

    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_addAfterResolve_hook_can_replace_the_resolved_instance(): void
    {
        $this->container->bind('TestService', fn () => new stdClass());
        $replacement = new stdClass();

        $this->container->addAfterResolve(function (object $instance, string $concrete) use ($replacement): ?object {
            return $concrete === 'TestService' ? $replacement : null;
        });

        $instance = $this->container->make('TestService');

        $this->assertSame($replacement, $instance);
    }
}
