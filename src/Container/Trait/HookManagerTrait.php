<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

/**
 * Trait HookManagerTrait
 *
 * Manages hooks to be executed before and after dependency resolution.
 */
trait HookManagerTrait
{
    /**
     * @var list<callable(string, array<string, mixed>): void>
     */
    protected array $beforeResolveHooks = [];

    /**
     * @var list<callable(object, string, array<string, mixed>): ?object>
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
     * @param callable(object, string, array<string, mixed>): ?object $hook
     * @return void
     */
    public function addAfterResolve(
        callable $hook
    ): void {
        $this->afterResolveHooks[] = $hook;
    }
}
