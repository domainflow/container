<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Attribute\Inject;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerPropertyInjectionIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException|NotFoundException
     */
    public function test_property_injection_resolves_correctly(): void
    {
        $container = new Container();
        $container->bind(LoggerInterfaceE::class, ConsoleLoggerG::class);

        ob_start();
        try {
            $userService = $container->make(UserService::class);
            $userService->registerUser("john_doe");
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString("[ConsoleLoggerG] User 'john_doe' has been registered.", $output);
    }
}

// dummy classes and interface
interface LoggerInterfaceE
{
    public function log(string $message): void;
}

class ConsoleLoggerG implements LoggerInterfaceE
{
    public function log(
        string $message
    ): void {
        echo "[ConsoleLoggerG] " . $message . PHP_EOL;
    }
}

class UserService
{
    #[Inject]
    private LoggerInterfaceE $logger;

    public function registerUser(
        string $username
    ): void {
        $this->logger->log("User '$username' has been registered.");
    }
}
