<?php

declare(strict_types=1);

namespace DomainFlow\Tests;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerBenchmarkTest extends TestCase
{
    /**
     * @throws Throwable| NotFoundException|ContainerException
     */
    public function test_container_resolution_vs_execution(): void
    {
        $container = new Container();
        $container->bind(BenchmarkService::class);

        $iterations = 10000;
        $startResolution = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $service = $container->make(BenchmarkService::class);
        }

        $endResolution = microtime(true);
        $resolutionTime = ($endResolution - $startResolution) * 1000 / $iterations;

        $startExecution = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $service->execute();
        }

        $endExecution = microtime(true);
        $executionTime = ($endExecution - $startExecution) * 1000 / $iterations;

        $this->assertLessThan(0.01, $resolutionTime, "Container resolution time should be < 0.01 ms per resolution");

        $output = sprintf(
            "🛠 Resolution Time: %.6f ms | Execution Time: %.6f ms per service call",
            $resolutionTime,
            $executionTime
        );

        if (getenv('DEBUG_BENCHMARK') === '1') {
            echo $output . PHP_EOL;
        }
    }
}

# dummy class
class BenchmarkService
{
    public function execute(): void
    {
        $result = 0;
        for ($i = 0; $i < 1000; $i++) {
            $result += sqrt($i) * cos($i) - sin($i);
        }
    }
}
