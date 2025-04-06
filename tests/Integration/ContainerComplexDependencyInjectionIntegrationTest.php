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
class ContainerComplexDependencyInjectionIntegrationTest extends TestCase
{
    /**
     * @throws NotFoundException|Throwable|ContainerException
     */
    public function test_complex_dependency_injection_resolves_correctly(): void
    {
        $container = new Container();

        $container->bind(LoggerInterfaceC::class, ConsoleLoggerB::class);
        $container->bind(CacheInterface::class, FileCache::class);

        ob_start();
        try {
            $reportService = $container->make(ReportGenerator::class);
            $reportService->generateReport();
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString("[Logger] Generating report...", $output);
        $this->assertStringContainsString("[FileCache] Stored report_123 => Generated Report Data", $output);
    }
}

// dummy classes and interfaces
interface CacheInterface
{
    public function store(string $key, string $value): void;
}

class FileCache implements CacheInterface
{
    public function store(
        string $key,
        string $value
    ): void {
        echo "[FileCache] Stored {$key} => {$value}" . PHP_EOL;
    }
}

class RedisCache implements CacheInterface
{
    public function store(
        string $key,
        string $value
    ): void {
        echo "[RedisCache] Stored {$key} => {$value}" . PHP_EOL;
    }
}

interface LoggerInterfaceC
{
    public function log(string $message): void;
}

class ConsoleLoggerB implements LoggerInterfaceC
{
    public function log(
        string $message
    ): void {
        echo "[Logger] " . $message . PHP_EOL;
    }
}

class ReportGenerator
{
    #[Inject]
    private LoggerInterfaceC $logger;

    #[Inject]
    private CacheInterface $cache;

    public function generateReport(): void
    {
        $this->logger->log("Generating report...");
        $this->cache->store('report_123', 'Generated Report Data');
    }
}
