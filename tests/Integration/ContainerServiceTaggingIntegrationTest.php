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
class ContainerServiceTaggingIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException|NotFoundException
     */
    public function test_service_tagging_resolves_correctly(): void
    {
        $container = new Container();

        $container->bind('cache.redis', fn () => new RedisCache2());
        $container->bind('cache.memcached', fn () => new MemcachedCache2());

        $container->tag('cache', ['cache.redis', 'cache.memcached']);

        $taggedServices = $container->getByTag('cache');
        $this->assertCount(2, $taggedServices, "Expected exactly 2 services tagged as 'cache'.");
        $this->assertContainsOnlyInstancesOf(CacheInterface1::class, $taggedServices, "All tagged services should implement CacheInterface.");

        ob_start();
        try {
            foreach ($taggedServices as $cache) {
                $cache->store('user_123', 'John Doe');
            }
        } finally {
            $output = ob_get_clean();
        }

        $this->assertStringContainsString("[RedisCache] Stored user_123 => John Doe", $output);
        $this->assertStringContainsString("[MemcachedCache] Stored user_123 => John Doe", $output);
    }
}

// dummy classes and interface
interface CacheInterface1
{
    public function store(string $key, string $value): void;
}

class RedisCache2 implements CacheInterface1
{
    public function store(
        string $key,
        string $value
    ): void {
        echo "[RedisCache] Stored {$key} => {$value}" . PHP_EOL;
    }
}

class MemcachedCache2 implements CacheInterface1
{
    public function store(
        string $key,
        string $value
    ): void {
        echo "[MemcachedCache] Stored {$key} => {$value}" . PHP_EOL;
    }
}
