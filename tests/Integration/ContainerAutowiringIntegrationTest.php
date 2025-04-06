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
class ContainerAutowiringIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|NotFoundException|ContainerException
     */
    public function test_autowiring_injects_correct_dependencies(): void
    {
        $container = new Container();

        $container->bind(LoggerInterfaceA::class, FileLogger::class);

        $reportService = $container->make(ReportService::class);

        ob_start();
        $reportService->generateReport();
        $output = ob_get_clean();

        $this->assertStringContainsString("[FileLogger] Report generated successfully.", $output);
    }
}

// dummy class and interface
interface LoggerInterfaceA
{
    public function log(string $message): void;
}

final readonly class FileLogger implements LoggerInterfaceA
{
    public function log(
        string $message
    ): void {
        echo "[FileLogger] " . $message . PHP_EOL;
    }
}

final readonly class ReportService
{
    private LoggerInterfaceA $logger;

    public function __construct(
        LoggerInterfaceA $logger
    ) {
        $this->logger = $logger;
    }

    public function generateReport(): void
    {
        $this->logger->log("Report generated successfully.");
    }
}
