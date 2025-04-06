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
class ContainerContextualBindingIntegrationTest extends TestCase
{
    /**
     * @throws ContainerException|NotFoundException|Throwable
     */
    public function test_contextual_bindings_resolve_correctly(): void
    {
        $container = new Container();

        $container->addContextualBinding(OrderController::class, LoggerInterfaceD::class, FileLoggerB::class);
        $container->addContextualBinding(AuthController::class, LoggerInterfaceD::class, DatabaseLogger::class);

        ob_start();
        try {
            $orderController = $container->make(OrderController::class);
            $authController = $container->make(AuthController::class);

            $orderController->processOrder();
            $authController->login();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString("[FileLoggerB] Order processed successfully.", $output);
        $this->assertStringContainsString("[DatabaseLogger] User logged in successfully.", $output);
    }
}

// dummy classes and interface
interface LoggerInterfaceD
{
    public function log(string $message): void;
}

class FileLoggerB implements LoggerInterfaceD
{
    public function log(
        string $message
    ): void {
        echo "[FileLoggerB] " . $message . PHP_EOL;
    }
}

class DatabaseLogger implements LoggerInterfaceD
{
    public function log(
        string $message
    ): void {
        echo "[DatabaseLogger] " . $message . PHP_EOL;
    }
}

readonly class OrderController
{
    public function __construct(
        private LoggerInterfaceD $logger
    ) {
    }

    public function processOrder(): void
    {
        $this->logger->log("Order processed successfully.");
    }
}

readonly class AuthController
{
    public function __construct(
        private LoggerInterfaceD $logger
    ) {
    }

    public function login(): void
    {
        $this->logger->log("User logged in successfully.");
    }
}
