<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(Container::class)]
final class ContainerContractRegressionTest extends TestCase
{
    public function test_static_array_callable_does_not_require_an_instance_and_injects_dependencies(): void
    {
        $container = new Container();

        $result = $container->call([ContractStaticUtility::class, 'run']);

        $this->assertSame(ContractCallableDependency::class, $result);
    }

    public function test_static_string_callable_is_resolved_as_a_method_and_injects_dependencies(): void
    {
        $container = new Container();

        $result = $container->call(ContractStaticUtility::class . '::run');

        $this->assertSame(ContractCallableDependency::class, $result);
    }

    public function test_explicit_null_instance_is_present_and_retrievable(): void
    {
        $container = new Container();
        $container->instance('nullable', null);

        $this->assertTrue($container->has('nullable'));
        $this->assertNull($container->get('nullable'));
    }

    public function test_null_singleton_factory_runs_only_once(): void
    {
        $container = new Container();
        $calls = 0;
        $container->singleton('nullable.singleton', static function () use (&$calls): null {
            ++$calls;

            return null;
        });

        $this->assertNull($container->get('nullable.singleton'));
        $this->assertNull($container->get('nullable.singleton'));
        $this->assertSame(1, $calls);
    }

    public function test_scope_retains_a_null_singleton_and_reports_it_as_present(): void
    {
        $container = new Container();
        $scope = $container->scope('nullable', static fn (Container $scope): Container => $scope);
        $calls = 0;
        $scope->singleton('nullable.singleton', static function () use (&$calls): null {
            ++$calls;

            return null;
        });

        $this->assertTrue($scope->has('nullable.singleton'));
        $this->assertNull($scope->get('nullable.singleton'));
        $this->assertNull($scope->make('nullable.singleton'));
        $this->assertSame(1, $calls);
    }

    public function test_scope_get_and_has_resolve_a_local_alias_consistently_with_make(): void
    {
        $container = new Container();
        $scope = $container->scope('alias', static fn (Container $scope): Container => $scope);
        $scope->bind(ContractScopedService::class);
        $scope->alias(ContractScopedService::class, 'contract.service');

        $this->assertTrue($scope->has('contract.service'));
        $this->assertInstanceOf(ContractScopedService::class, $scope->get('contract.service'));
        $this->assertInstanceOf(ContractScopedService::class, $scope->make('contract.service'));
    }

    public function test_local_scope_alias_takes_priority_over_a_parent_alias_with_the_same_name(): void
    {
        $container = new Container();
        $container->bind(ContractParentAliasTarget::class);
        $container->alias(ContractParentAliasTarget::class, 'contract.service');

        $scope = $container->scope('shadowing', static fn (Container $scope): Container => $scope);
        $scope->bind(ContractLocalAliasTarget::class);
        $scope->alias(ContractLocalAliasTarget::class, 'contract.service');

        $this->assertTrue($scope->has('contract.service'));
        $this->assertInstanceOf(ContractLocalAliasTarget::class, $scope->get('contract.service'));
        $this->assertInstanceOf(ContractLocalAliasTarget::class, $scope->make('contract.service'));
    }

    public function test_before_hook_recursion_is_detected_as_a_circular_dependency(): void
    {
        $container = new Container();
        $nested = false;
        $container->addBeforeResolve(static function (string $id) use ($container, &$nested): void {
            if ($id === ContractBeforeHookService::class && !$nested) {
                $nested = true;
                $container->make($id);
            }
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        $container->make(ContractBeforeHookService::class);
    }

    public function test_throwing_before_hook_does_not_leave_stale_resolution_state(): void
    {
        $container = new Container();
        $shouldThrow = true;
        $container->addBeforeResolve(static function (string $id) use (&$shouldThrow): void {
            if ($id === ContractBeforeHookService::class && $shouldThrow) {
                $shouldThrow = false;

                throw new RuntimeException('Expected first resolution failure.');
            }
        });

        try {
            $container->make(ContractBeforeHookService::class);
            $this->fail('The first resolution should fail in the before-hook.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Expected first resolution failure.', $exception->getMessage());
        }

        $this->assertInstanceOf(ContractBeforeHookService::class, $container->make(ContractBeforeHookService::class));
    }

    public function test_after_hooks_receive_and_preserve_non_object_values_without_casting(): void
    {
        $container = new Container();
        $observed = null;
        $container->bind('scalar', static fn (): int => 5);
        $container->addAfterResolve(static function (mixed $value) use (&$observed): mixed {
            $observed = $value;

            return $value;
        });

        $this->assertSame(5, $container->get('scalar'));
        $this->assertSame(5, $observed);
    }
}

final class ContractCallableDependency
{
}

abstract class ContractStaticUtility
{
    final public static function run(ContractCallableDependency $dependency): string
    {
        return $dependency::class;
    }
}

final class ContractScopedService
{
}

final class ContractParentAliasTarget
{
}

final class ContractLocalAliasTarget
{
}

final class ContractBeforeHookService
{
}
