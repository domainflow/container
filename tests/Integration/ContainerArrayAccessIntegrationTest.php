<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class ContainerArrayAccessIntegrationTest extends TestCase
{
    public function test_array_access_for_services(): void
    {
        $container = new Container();

        $container['db'] = new DatabaseConnection();
        $container['logger'] = fn () => new class() implements LoggerInterface {
            public function log(string $message): string
            {
                return "[Logger] " . $message;
            }
        };

        $db1 = $container['db'];
        $db2 = $container['db'];

        $this->assertSame($db1, $db2, "ArrayAccess should return the same instance for 'db'.");

        /** @var LoggerInterface $logger */
        $logger = $container['logger'];
        $loggerOutput = $logger->log("ArrayAccess works!");
        $this->assertEquals("[Logger] ArrayAccess works!", $loggerOutput);
    }
}

// dummy class and interface
interface LoggerInterface
{
    public function log(string $message): string;
}

class DatabaseConnection
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
