<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

/**
 * Trait HookManagerTrait
 *
 * Manages hooks executed by Container::make() before and after resolution.
 */
trait HookManagerTrait
{
    /**
     * @var list<callable(string, array<string, mixed>): void>
     */
    protected array $beforeResolveHooks = [];

    /**
     * @var list<callable(mixed, string, array<string, mixed>): mixed>
     */
    protected array $afterResolveHooks = [];

    /**
     * Register a hook to be executed before resolving a dependency.
     *
     * @param callable(string, array<string, mixed>): void $hook
     * @return void
     */
    public function addBeforeResolve(
        callable $hook
    ): void {
        $this->beforeResolveHooks[] = $hook;
    }

    /**
     * Register a hook to be executed after resolving a dependency.
     *
     * @param callable(mixed, string, array<string, mixed>): mixed $hook
     * @return void
     */
    public function addAfterResolve(
        callable $hook
    ): void {
        $this->afterResolveHooks[] = $hook;
    }
}
