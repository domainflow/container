<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Cache\ContainerCacheInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
class ContainerCachingIntegrationTest extends TestCase
{
    private string $cacheFile;
    private FileContainerCache $fileCache;
    private string $serviceKey = 'heavyService';

    protected function setUp(): void
    {
        $this->cacheFile = __DIR__ . '/container_cache.json';
        $container = new Container();
        $this->fileCache = new FileContainerCache($this->cacheFile);
        $container->setExternalCache($this->fileCache);
    }

    public function test_cache_creates_new_instance(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        $this->assertFalse($this->fileCache->has($this->serviceKey), "Cache should be empty before first resolution.");

        $heavyService = new HeavyService();
        $this->fileCache->set($this->serviceKey, $heavyService);

        $this->assertTrue($this->fileCache->has($this->serviceKey), "Cache should contain the service after storing it.");
    }

    public function test_cache_loads_existing_instance(): void
    {
        if (!$this->fileCache->has($this->serviceKey)) {
            $preCachedService = new HeavyService();
            $this->fileCache->set($this->serviceKey, $preCachedService);
        }

        $cachedService = $this->fileCache->get($this->serviceKey);

        $this->assertNotNull($cachedService, "Cached instance should not be null.");
        $this->assertInstanceOf(HeavyService::class, $cachedService, "Loaded instance should be of type HeavyService.");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }
}

// dummy classes
class FileContainerCache implements ContainerCacheInterface
{
    private string $cacheFile;

    public function __construct(string $cacheFile)
    {
        $this->cacheFile = $cacheFile;
    }

    public function get(
        string $key
    ): mixed {
        if (!file_exists($this->cacheFile)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->cacheFile), true);

        return isset($data[$key]) ? unserialize($data[$key]) : null;
    }

    public function set(
        string $key,
        mixed $value,
        int $ttl = 3600
    ): bool {
        $data = file_exists($this->cacheFile) ? json_decode(file_get_contents($this->cacheFile), true) : [];
        $data[$key] = serialize($value);
        file_put_contents($this->cacheFile, json_encode($data));

        return true;
    }

    public function has(
        string $key
    ): bool {
        return file_exists($this->cacheFile) && isset(json_decode(file_get_contents($this->cacheFile), true)[$key]);
    }

    public function delete(
        string $key
    ): bool {
        if (!$this->has($key)) {
            return false;
        }
        $data = json_decode(file_get_contents($this->cacheFile), true);
        unset($data[$key]);
        file_put_contents($this->cacheFile, json_encode($data));

        return true;
    }
}

class HeavyService
{
    private string $instanceId;

    public function __construct()
    {
        sleep(1);
        $this->instanceId = uniqid('heavy_', true);
    }

    public function getInstanceId(): string
    {
        return $this->instanceId;
    }
}
