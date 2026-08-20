<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[CoversClass(Container::class)]
final class PsrContainerTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    /**
     * @throws NotFoundException| ContainerException
     */
    public function test_get_returns_registered_instance(): void
    {
        $dummyService = new stdClass();
        $this->container->instance('DummyService', $dummyService);

        $retrievedService = $this->container->get('DummyService');
        $this->assertSame($dummyService, $retrievedService);
    }

    /**
     * @throws ContainerException
     */
    public function test_get_throws_NotFoundException_for_unregistered_service(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage("No entry found for [NonExistentService].");

        $this->container->get('NonExistentService');
    }

    /**
     * @throws NotFoundException
     */
    public function test_get_wraps_exceptions_in_ContainerException(): void
    {
        $this->container->bind('FaultyService', static function (): never {
            throw new RuntimeException("Something went wrong");
        });

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage("Error while retrieving the entry 'FaultyService': Something went wrong");

        $this->container->get('FaultyService');
    }

    public function test_get_preserves_NotFoundException_thrown_during_resolution(): void
    {
        $expected = new NotFoundException('Dependency disappeared during resolution.');
        $container = new class($expected) extends Container {
            public function __construct(private readonly NotFoundException $failure)
            {
            }

            public function has(string $id): bool
            {
                return true;
            }

            public function make(string $abstract, array $parameters = []): mixed
            {
                throw $this->failure;
            }
        };

        try {
            $container->get('TransientService');
            $this->fail('Expected the resolution failure to be rethrown.');
        } catch (NotFoundException $actual) {
            $this->assertSame($expected, $actual);
        }
    }

    public function test_has_returns_true_for_registered_service(): void
    {
        $this->container->instance('ExistingService', new stdClass());

        $this->assertTrue($this->container->has('ExistingService'));
    }

    public function test_has_returns_false_for_unregistered_service(): void
    {
        $this->assertFalse($this->container->has('UnknownService'));
    }
}
