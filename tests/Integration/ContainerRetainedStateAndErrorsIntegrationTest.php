<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Attribute\Inject;
use DomainFlow\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversNothing]
final class ContainerRetainedStateAndErrorsIntegrationTest extends TestCase
{
    public function test_rebinding_a_resolved_shared_binding_discards_its_retained_instance(): void
    {
        $container = new Container();
        $first = new stdClass();
        $second = new stdClass();

        $container->singleton('service', static fn (): stdClass => $first);
        $this->assertSame($first, $container->make('service'));

        $container->singleton('service', static fn (): stdClass => $second);

        $this->assertSame($second, $container->make('service'));
    }

    public function test_reset_scope_discards_retained_instances_but_keeps_its_configuration(): void
    {
        $container = new Container();
        $scope = $container->scope('request', static fn (Container $scope): Container => $scope);
        $created = 0;
        $scope->singleton('request.service', static function () use (&$created): stdClass {
            ++$created;

            return new stdClass();
        });

        $first = $scope->make('request.service');
        $container->resetScope('request');
        $second = $scope->make('request.service');

        $this->assertNotSame($first, $second);
        $this->assertSame(2, $created);
    }

    public function test_dispose_scope_removes_its_bindings_and_instances(): void
    {
        $container = new Container();
        $container->scope('request', static function (Container $scope): void {
            $scope->instance('request.service', new stdClass());
        });

        $container->disposeScope('request');

        $this->assertFalse($container->scope('request', static fn (Container $scope): bool => $scope->has('request.service')));
    }

    public function test_has_does_not_advertise_an_uninstantiable_autowire_candidate(): void
    {
        $container = new Container();

        $this->assertFalse($container->has(ContainerRetainedStateAbstractService::class));
    }

    public function test_invalid_inject_property_type_has_a_useful_container_error(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot inject property [' . InvalidInjectPropertyType::class . '::$dependency]: union types are not supported.');

        $container->make(InvalidInjectPropertyType::class);
    }
}

abstract class ContainerRetainedStateAbstractService
{
}

final class InvalidInjectPropertyType
{
    #[Inject]
    private string|stdClass $dependency;
}
