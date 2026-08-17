<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Cache\ContainerCacheInterface;
use DomainFlow\Container\Cache\InMemoryContainerCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(InMemoryContainerCache::class)]
final class ContainerCachingIntegrationTest extends TestCase
{
    public function test_warm_container_rebuilds_cached_declarative_binding(): void
    {
        $cache = new InMemoryContainerCache();
        $coldContainer = new Container();
        $coldContainer->setExternalCache($cache);
        $coldContainer->singleton(HeavyServiceContract::class, HeavyService::class);
        $coldContainer->alias(HeavyServiceContract::class, 'heavy');

        $coldInstance = $coldContainer->make(HeavyServiceContract::class);

        $cachedDefinitions = $cache->get(ContainerCacheInterface::DEFINITION_CACHE_KEY);

        $this->assertSame(
            [
                'version' => 1,
                'bindings' => [
                    HeavyServiceContract::class => ['concrete' => HeavyService::class, 'shared' => true],
                ],
                'aliases' => ['heavy' => HeavyServiceContract::class],
            ],
            $cachedDefinitions
        );

        $warmContainer = new Container();
        $warmContainer->setExternalCache($cache);
        $warmInstance = $warmContainer->get('heavy');

        $this->assertInstanceOf(HeavyService::class, $warmInstance);
        $this->assertNotSame($coldInstance, $warmInstance);
    }

    public function test_closure_bindings_are_not_written_to_the_definition_cache(): void
    {
        $cache = new InMemoryContainerCache();
        $container = new Container();
        $container->setExternalCache($cache);
        $container->bind('factory', static fn (): HeavyService => new HeavyService());

        $cachedDefinitions = $cache->get(ContainerCacheInterface::DEFINITION_CACHE_KEY);

        $this->assertSame(['version' => 1, 'bindings' => [], 'aliases' => []], $cachedDefinitions);
    }

    public function test_alias_to_a_closure_binding_is_not_written_to_the_definition_cache(): void
    {
        $cache = new InMemoryContainerCache();
        $container = new Container();
        $container->setExternalCache($cache);
        $container->bind('factory', static fn (): HeavyService => new HeavyService());
        $container->alias('factory', 'factory_alias');

        $cachedDefinitions = $cache->get(ContainerCacheInterface::DEFINITION_CACHE_KEY);

        $this->assertSame(['version' => 1, 'bindings' => [], 'aliases' => []], $cachedDefinitions);
    }

    public function test_alias_to_an_autowireable_class_is_written_to_the_definition_cache(): void
    {
        $cache = new InMemoryContainerCache();
        $container = new Container();
        $container->setExternalCache($cache);
        $container->alias(HeavyService::class, 'heavy');

        $cachedDefinitions = $cache->get(ContainerCacheInterface::DEFINITION_CACHE_KEY);

        $this->assertSame(['version' => 1, 'bindings' => [], 'aliases' => ['heavy' => HeavyService::class]], $cachedDefinitions);
    }

    public function test_invalid_cached_definitions_are_ignored(): void
    {
        $cache = new InMemoryContainerCache();
        $cache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, ['version' => 2]);

        $container = new Container();
        $container->setExternalCache($cache);

        $this->assertFalse($container->has(HeavyServiceContract::class));
    }

    public function test_cached_definition_with_an_invalid_binding_is_ignored(): void
    {
        $cache = new InMemoryContainerCache();
        $cache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, [
            'version' => 1,
            'bindings' => [HeavyServiceContract::class => ['concrete' => 'missing-class', 'shared' => true]],
            'aliases' => [],
        ]);

        $container = new Container();
        $container->setExternalCache($cache);

        $this->assertFalse($container->has(HeavyServiceContract::class));
    }

    public function test_cached_definition_with_an_invalid_alias_is_ignored(): void
    {
        $cache = new InMemoryContainerCache();
        $cache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, [
            'version' => 1,
            'bindings' => [],
            'aliases' => ['' => HeavyServiceContract::class],
        ]);

        $container = new Container();
        $container->setExternalCache($cache);

        $this->assertFalse($container->has(HeavyServiceContract::class));
    }

    public function test_cached_definition_with_unknown_fields_is_ignored(): void
    {
        $cache = new InMemoryContainerCache();
        $cache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, [
            'version' => 1,
            'bindings' => [],
            'aliases' => [],
            'unexpected' => true,
        ]);

        $container = new Container();
        $container->setExternalCache($cache);

        $this->assertFalse($container->has(HeavyServiceContract::class));
    }

    public function test_existing_container_definitions_are_not_overwritten_when_a_cache_is_attached(): void
    {
        $cache = new InMemoryContainerCache();
        $cache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, [
            'version' => 1,
            'bindings' => [HeavyServiceContract::class => ['concrete' => HeavyService::class, 'shared' => false]],
            'aliases' => [],
        ]);

        $container = new Container();
        $container->bind('local', HeavyService::class);
        $container->setExternalCache($cache);

        $this->assertFalse($container->has(HeavyServiceContract::class));
        $this->assertInstanceOf(HeavyService::class, $container->make('local'));
    }

    public function test_definition_cache_is_written_without_expiration(): void
    {
        $cache = new RecordingCache();
        $container = new Container();
        $container->setExternalCache($cache);
        $container->bind(HeavyServiceContract::class, HeavyService::class);

        $this->assertSame(0, $cache->lastTtl);
    }

    public function test_clear_resolution_cache_keeps_local_bindings_resolvable(): void
    {
        $cache = new InMemoryContainerCache();
        $container = new Container();
        $container->setExternalCache($cache);
        $container->singleton(HeavyServiceContract::class, HeavyService::class);

        $container->clearResolutionCache();

        $this->assertFalse($cache->has(ContainerCacheInterface::DEFINITION_CACHE_KEY));
        $this->assertInstanceOf(HeavyService::class, $container->make(HeavyServiceContract::class));
    }
}

interface HeavyServiceContract
{
}

final class HeavyService implements HeavyServiceContract
{
}

final class RecordingCache implements ContainerCacheInterface
{
    public int $lastTtl = -1;

    public function get(string $key): mixed
    {
        return null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $this->lastTtl = $ttl;

        return true;
    }

    public function has(string $key): bool
    {
        return false;
    }

    public function delete(string $key): bool
    {
        return true;
    }
}
