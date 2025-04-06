<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Cache\ContainerCacheInterface;
use DomainFlow\Tests\Unit\Dummy\DummyCacheManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

#[CoversClass(Container::class)]
final class CacheManagerTraitTest extends TestCase
{
    private DummyCacheManager $dummy;

    protected function setUp(): void
    {
        $this->dummy = new DummyCacheManager();
    }

    public function test_cacheResolvedService_and_cacheResolvedServices(): void
    {
        $this->dummy->cacheResolvedService('service1', 'instance1');
        $this->dummy->cacheResolvedService('service2', 'instance2');

        $cache = $this->dummy->cacheResolvedServices();

        $this->assertCount(2, $cache);
        $this->assertSame('instance1', $cache['service1']);
        $this->assertSame('instance2', $cache['service2']);
    }

    public function test_clearResolvedServicesCache(): void
    {
        $this->dummy->cacheResolvedService('service1', 'instance1');
        $this->dummy->clearResolvedServicesCache(
            fn (string $abstract, mixed $instance) => $this->assertSame('service1', $abstract)
        );
        $cache = $this->dummy->cacheResolvedServices();
        $this->assertEmpty($cache);
    }

    public function test_setExternalCache_sets_the_external_cache(): void
    {
        $dummyCache = new class() implements ContainerCacheInterface {
            /**
             * @var array<string, mixed>
             */
            private array $store = [];
            public function get(string $key): mixed
            {
                return $this->store[$key] ?? null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): bool
            {
                $this->store[$key] = $value;

                return true;
            }
            public function has(string $key): bool
            {
                return array_key_exists($key, $this->store);
            }
            public function delete(string $key): bool
            {
                unset($this->store[$key]);

                return true;
            }
        };

        $this->dummy->setExternalCache($dummyCache);

        $reflection = new ReflectionClass($this->dummy);
        $prop = $reflection->getProperty('externalCache');

        $this->assertSame($dummyCache, $prop->getValue($this->dummy));
    }

    /**
     * @throws ReflectionException
     */
    public function test_closureToString_returns_consistent_unique_string(): void
    {
        $closure1 = function () { return 'test1'; };
        $closure2 = function () { return 'test2'; };

        $reflection = new ReflectionClass($this->dummy);
        $method = $reflection->getMethod('closureToString');

        $hash1 = $method->invoke($this->dummy, $closure1);
        $hash2 = $method->invoke($this->dummy, $closure1);
        $hash3 = $method->invoke($this->dummy, $closure2);

        $this->assertSame($hash1, $hash2);
        $this->assertNotSame($hash1, $hash3);
        $this->assertSame(spl_object_hash($closure1), $hash1);
    }

    public function test_loadResolvedServicesFromExternalCache(): void
    {
        $dummyCache = new class() implements ContainerCacheInterface {
            /**
             * @var array<string, mixed>
             */
            private array $store = [];
            public function get(string $key): mixed
            {
                return $this->store[$key] ?? null;
            }
            public function set(string $key, mixed $value, int $ttl = 3600): bool
            {
                $this->store[$key] = $value;

                return true;
            }
            public function has(string $key): bool
            {
                return array_key_exists($key, $this->store);
            }
            public function delete(string $key): bool
            {
                unset($this->store[$key]);

                return true;
            }
        };

        $this->dummy->setExternalCache($dummyCache);

        $cacheKey = 'testKey';
        $cachedData = [
            'service1' => 'cachedInstance1',
            'service2' => 'cachedInstance2',
        ];
        $dummyCache->set($cacheKey, $cachedData);

        $this->dummy->cacheResolvedService('otherService', 'otherInstance');
        $this->assertNotSame($cachedData, $this->dummy->cacheResolvedServices());

        $this->dummy->loadResolvedServicesFromExternalCache($cacheKey);

        $this->assertSame($cachedData, $this->dummy->cacheResolvedServices());
    }

    public function test_clearResolvedServicesCache_calls_external_cache_set(): void
    {
        $dummyExternalCache = new class() implements ContainerCacheInterface {
            public string$lastKey;

            public mixed $lastValue;

            public function get(string $key): mixed
            {
                return $this->lastValue;
            }

            /**
             * @param string $key
             * @param mixed $value
             * @param int $ttl
             * @return bool
             */
            public function set(string $key, mixed $value, int $ttl = 3600): bool
            {
                $this->lastKey = $key;
                $this->lastValue = $value;

                return true;
            }
            public function has(string $key): bool
            {
                return isset($this->lastKey);
            }
            public function delete(string $key): bool
            {
                unset($this->lastKey, $this->lastValue);

                return true;
            }
        };

        $this->dummy->setExternalCache($dummyExternalCache);

        $this->dummy->cacheResolvedService('service1', 'instance1');
        $this->dummy->cacheResolvedService('service2', 'instance2');

        $closure = function (string $abstract, mixed $instance): void {
            // extra logic not needed for testing
        };
        $this->dummy->clearResolvedServicesCache($closure);

        $expectedKey = spl_object_hash($closure);
        $this->assertSame($expectedKey, $dummyExternalCache->lastKey);

        $this->assertSame(
            ['service1' => 'instance1', 'service2' => 'instance2'],
            $dummyExternalCache->lastValue
        );

        $this->assertEmpty($this->dummy->cacheResolvedServices());
    }

}
