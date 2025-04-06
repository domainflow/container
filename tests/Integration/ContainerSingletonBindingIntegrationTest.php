<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerSingletonBindingIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException|NotFoundException
     */
    public function test_singleton_binding_resolves_same_instance(): void
    {
        $container = new Container();

        $container->singleton('database', fn () => new DatabaseConnectionB());

        $db1 = $container->make('database');
        $db2 = $container->make('database');

        $this->assertSame($db1, $db2, "Singleton instance should be the same across multiple resolutions.");
    }
}

// dummy class
class DatabaseConnectionB
{
    private string $connectionId;

    public function __construct()
    {
        $this->connectionId = uniqid('db_', true);
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }
}
