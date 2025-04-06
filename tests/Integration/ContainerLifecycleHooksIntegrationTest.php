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
class ContainerLifecycleHooksIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException|NotFoundException
     */
    public function test_lifecycle_hooks_are_triggered_correctly(): void
    {
        $container = new Container();

        ob_start();
        try {
            $container->addBeforeResolve(function (string $concrete, array $parameters): void {
                echo "Resolving: $concrete" . PHP_EOL;
            });

            $container->addAfterResolve(function (object $instance, string $concrete, array $parameters): ?object {
                echo "Resolved: $concrete" . PHP_EOL;

                return null;
            });

            $container->bind(Logger::class);

            $container->resetContainer();

            $logger = $container->make(Logger::class);
            $logger->log("Logging event hooks in action!");
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString("Resolving: " . Logger::class, $output);
        $this->assertStringContainsString("Resolved: " . Logger::class, $output);
        $this->assertStringContainsString("[Logger] Logging event hooks in action!", $output);
    }
}

// dummy class
class Logger
{
    public function log(
        string $message
    ): void {
        echo "[Logger] " . $message . PHP_EOL;
    }
}
