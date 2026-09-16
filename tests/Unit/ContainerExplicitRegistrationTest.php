<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit;

use DomainFlow\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerExplicitRegistrationTest extends TestCase
{
    public function test_explicit_binding_is_reported_without_executing_its_factory(): void
    {
        $container = new Container();
        $calls = 0;
        $container->bind('service', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $this->assertTrue($container->hasExplicitRegistration('service'));
        $this->assertSame(0, $calls);
    }

    public function test_explicit_null_instance_is_reported(): void
    {
        $container = new Container();
        $container->instance('nullable', null);

        $this->assertTrue($container->hasExplicitRegistration('nullable'));
        $this->assertNull($container->get('nullable'));
    }

    public function test_alias_identifier_and_target_report_their_own_registration_state(): void
    {
        $container = new Container();
        $container->alias(ExplicitRegistrationAutowireable::class, 'service.alias');

        $this->assertTrue($container->hasExplicitRegistration('service.alias'));
        $this->assertFalse($container->hasExplicitRegistration(ExplicitRegistrationAutowireable::class));

        $container->bind(ExplicitRegistrationAutowireable::class);

        $this->assertTrue($container->hasExplicitRegistration(ExplicitRegistrationAutowireable::class));
    }

    public function test_autowireable_and_unknown_identifiers_are_not_explicit_registrations(): void
    {
        $container = new Container();

        $this->assertTrue($container->has(ExplicitRegistrationAutowireable::class));
        $this->assertFalse($container->hasExplicitRegistration(ExplicitRegistrationAutowireable::class));
        $this->assertFalse($container->hasExplicitRegistration('unknown.service'));
    }

    public function test_inspection_does_not_attempt_to_autoload_the_identifier(): void
    {
        $container = new Container();
        $autoloadCalls = 0;
        $autoloader = static function () use (&$autoloadCalls): void {
            ++$autoloadCalls;
        };
        spl_autoload_register($autoloader);

        try {
            $this->assertFalse($container->hasExplicitRegistration('Unknown\\Service'));
            $this->assertSame(0, $autoloadCalls);
        } finally {
            spl_autoload_unregister($autoloader);
        }
    }

    public function test_shared_binding_remains_explicit_before_and_after_resolution(): void
    {
        $container = new Container();
        $calls = 0;
        $container->singleton('shared.service', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $this->assertTrue($container->hasExplicitRegistration('shared.service'));
        $this->assertSame(0, $calls);

        $resolved = $container->get('shared.service');

        $this->assertTrue($container->hasExplicitRegistration('shared.service'));
        $this->assertSame($resolved, $container->get('shared.service'));
        $this->assertSame(1, $calls);
    }

    public function test_scope_reports_local_and_parent_explicit_registrations_without_autowiring(): void
    {
        $container = new Container();
        $container->bind('parent.service', static fn (): stdClass => new stdClass());
        $scope = $container->scope('request', static fn (Container $scope): Container => $scope);
        $scope->instance('local.nullable', null);
        $scope->alias(ExplicitRegistrationAutowireable::class, 'local.alias');

        $this->assertTrue($scope->hasExplicitRegistration('parent.service'));
        $this->assertTrue($scope->hasExplicitRegistration('local.nullable'));
        $this->assertTrue($scope->hasExplicitRegistration('local.alias'));
        $this->assertFalse($scope->hasExplicitRegistration(ExplicitRegistrationAutowireable::class));
        $this->assertFalse($scope->hasExplicitRegistration('unknown.service'));
    }
}

final class ExplicitRegistrationAutowireable
{
}
