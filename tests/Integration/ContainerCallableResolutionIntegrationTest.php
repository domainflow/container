<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerCallableResolutionIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException
     */
    public function test_callable_resolution_with_dependencies(): void
    {
        $container = new Container();

        $container->bind(LoggerInterfaceB::class, ConsoleLogger::class);

        ob_start();

        $container->call(fn () => processTask(
            $container->make(
                LoggerInterfaceB::class
            )
        ));

        $taskRunner = new TaskRunner();
        $container->call([$taskRunner, 'run']);

        $output = ob_get_clean();

        $this->assertStringContainsString("[ConsoleLogger] Task has been processed.", $output);
        $this->assertStringContainsString("[ConsoleLogger] Running task inside TaskRunner.", $output);
    }
}

// Dummy classes and interface
interface LoggerInterfaceB
{
    public function log(string $message): void;
}

class ConsoleLogger implements LoggerInterfaceB
{
    public function log(
        string $message
    ): void {
        echo "[ConsoleLogger] " . $message . PHP_EOL;
    }
}

function processTask(
    LoggerInterfaceB $logger
): void {
    $logger->log("Task has been processed.");
}

class TaskRunner
{
    public function run(
        LoggerInterfaceB $logger
    ): void {
        $logger->log("Running task inside TaskRunner.");
    }
}
