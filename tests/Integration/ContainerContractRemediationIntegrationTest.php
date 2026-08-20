<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Attribute\Inject;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

#[CoversClass(Container::class)]
final class ContainerContractRemediationIntegrationTest extends TestCase
{
    public function test_after_hook_replacement_is_retained_for_shared_bindings_and_aliases(): void
    {
        $container = new Container();
        $created = new stdClass();
        $replacement = new stdClass();
        $container->singleton('service', static fn (): stdClass => $created);
        $container->alias('service', 'service.alias');
        $container->addAfterResolve(static function () use ($replacement): ?stdClass {
            static $hasReplaced = false;

            if ($hasReplaced) {
                return null;
            }

            $hasReplaced = true;

            return $replacement;
        });

        $first = $container->make('service');
        $second = $container->make('service.alias');

        $this->assertSame($replacement, $first);
        $this->assertSame($replacement, $second);
    }

    public function test_scope_reset_preserves_local_alias_contextual_binding_hooks_and_tags(): void
    {
        $container = new Container();
        $container->singleton(ScopeDependency::class, ScopeParentDependency::class);
        $container->alias(ScopeDependency::class, 'parent.dependency');
        $hookCalls = 0;
        $scope = $container->scope('request', static fn (Container $scope): Container => $scope);
        $scope->singleton(ScopeDependency::class, ScopeLocalDependency::class);
        $scope->alias(ScopeDependency::class, 'scope.dependency');
        $scope->addContextualBinding(ScopeConsumer::class, ScopeDependency::class, ScopeContextualDependency::class);
        $scope->addAfterResolve(static function () use (&$hookCalls): null {
            ++$hookCalls;

            return null;
        });
        $scope->tag('scope.dependencies', [ScopeDependency::class]);
        $scope->cacheResolvedService('scope.legacy', new stdClass());

        $first = $scope->make('scope.dependency');
        $firstConsumer = $scope->make(ScopeConsumer::class);
        $this->assertInstanceOf(ScopeLocalDependency::class, $first);
        $this->assertInstanceOf(ScopeContextualDependency::class, $firstConsumer->dependency);
        $this->assertInstanceOf(ScopeLocalDependency::class, $scope->getByTag('scope.dependencies')[ScopeDependency::class]);

        $container->resetScope('request');

        $this->assertSame([], $scope->cacheResolvedServices());
        $second = $scope->make('scope.dependency');
        $secondConsumer = $scope->make(ScopeConsumer::class);
        $this->assertNotSame($first, $second);
        $this->assertInstanceOf(ScopeLocalDependency::class, $second);
        $this->assertInstanceOf(ScopeContextualDependency::class, $secondConsumer->dependency);
        $this->assertInstanceOf(ScopeLocalDependency::class, $scope->getByTag('scope.dependencies')[ScopeDependency::class]);
        $this->assertSame(6, $hookCalls);
        $this->assertInstanceOf(ScopeParentDependency::class, $container->make(ScopeDependency::class));
        $this->assertInstanceOf(ScopeParentDependency::class, $scope->make('parent.dependency'));
    }

    public function test_dispose_scope_removes_all_local_customization(): void
    {
        $container = new Container();
        $hookCalls = 0;
        $container->scope('request', static function (Container $scope) use (&$hookCalls): void {
            $scope->singleton(ScopeDependency::class, ScopeLocalDependency::class);
            $scope->alias(ScopeDependency::class, 'scope.dependency');
            $scope->addContextualBinding(ScopeConsumer::class, ScopeDependency::class, ScopeContextualDependency::class);
            $scope->addAfterResolve(static function () use (&$hookCalls): null {
                ++$hookCalls;

                return null;
            });
            $scope->tag('scope.dependencies', [ScopeDependency::class]);
            $scope->make('scope.dependency');
        });

        $this->assertSame(1, $hookCalls);

        $container->disposeScope('request');
        $freshScope = $container->scope('request', static fn (Container $scope): Container => $scope);

        $this->assertFalse($freshScope->has('scope.dependency'));
        $this->assertSame([], $freshScope->getByTag('scope.dependencies'));
        $freshScope->make(AutowireableUnboundEntry::class);
        $this->assertSame(1, $hookCalls);
        $this->expectException(NotFoundException::class);
        $freshScope->make(ScopeConsumer::class);
    }

    public function test_has_reports_autowireable_entries_that_get_can_return(): void
    {
        $container = new Container();

        $this->assertTrue($container->has(AutowireableUnboundEntry::class));
        $this->assertInstanceOf(AutowireableUnboundEntry::class, $container->get(AutowireableUnboundEntry::class));
        $this->assertFalse($container->has('missing.entry'));
    }

    public function test_scope_get_autowires_an_unbound_class_inside_the_scope(): void
    {
        $container = new Container();
        $scope = $container->scope('request', static fn (Container $scope): Container => $scope);

        $this->assertInstanceOf(AutowireableUnboundEntry::class, $scope->get(AutowireableUnboundEntry::class));
    }

    public function test_priority_union_with_no_resolvable_candidate_fails_through_make(): void
    {
        $container = new Container();
        $container->setUnionTypePriority(PriorityMissingUnionConsumer::class . '::$dependency', [MissingUnionA::class]);

        try {
            $container->make(PriorityMissingUnionConsumer::class);
            $this->fail('Expected an unresolvable priority union to fail.');
        } catch (ContainerException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_unprioritized_union_with_no_resolvable_candidate_fails_through_make(): void
    {
        try {
            (new Container())->make(UnprioritizedMissingUnionConsumer::class);
            $this->fail('Expected an unresolvable union to fail.');
        } catch (ContainerException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_invalid_injected_property_shapes_fail_through_the_public_property_injection_api(): void
    {
        foreach ([
            new InvalidUntypedInjectedProperty(),
            new InvalidBuiltinInjectedProperty(),
            new InvalidUnionInjectedProperty(),
            new InvalidIntersectionInjectedProperty(),
            new InvalidReadonlyInjectedProperty(),
        ] as $instance) {
            try {
                (new Container())->injectProperties($instance);
                $this->fail('Expected invalid property injection to fail.');
            } catch (ContainerException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}

interface ScopeDependency
{
}

final class ScopeParentDependency implements ScopeDependency
{
}

final class ScopeLocalDependency implements ScopeDependency
{
}

final class ScopeContextualDependency implements ScopeDependency
{
}

final readonly class ScopeConsumer
{
    public function __construct(public ScopeDependency $dependency)
    {
    }
}

final class AutowireableUnboundEntry
{
}

interface MissingUnionA
{
}

interface MissingUnionB
{
}

final readonly class PriorityMissingUnionConsumer
{
    public function __construct(public MissingUnionA|MissingUnionB $dependency)
    {
    }
}

final readonly class UnprioritizedMissingUnionConsumer
{
    public function __construct(public MissingUnionA|MissingUnionB $dependency)
    {
    }
}

final class InvalidUntypedInjectedProperty
{
    #[Inject]
    private $dependency;
}

final class InvalidBuiltinInjectedProperty
{
    #[Inject]
    private string $dependency;
}

final class InvalidUnionInjectedProperty
{
    #[Inject]
    private string|stdClass $dependency;
}

final class InvalidIntersectionInjectedProperty
{
    #[Inject]
    private ScopeDependency&Stringable $dependency;
}

final class InvalidReadonlyInjectedProperty
{
    #[Inject]
    private readonly stdClass $dependency;
}
