<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container\Cache\ContainerCacheInterface;

/**
 * Trait CacheManagerTrait
 *
 * Provides methods for caching resolved service instances.
 */
trait CacheManagerTrait
{
    /**
     * @var array<string, mixed>
     */
    protected array $resolvedServicesCache = [];

    /**
     * External cache store (optional).
     *
     * @var ContainerCacheInterface|null
     */
    protected ?ContainerCacheInterface $externalCache = null;

    /**
     * Set the external cache store.
     *
     * @param ContainerCacheInterface $cacheStore
     * @return void
     */
    public function setExternalCache(
        ContainerCacheInterface $cacheStore
    ): void {
        $this->externalCache = $cacheStore;
    }

    /**
     * Cache a resolved service instance.
     *
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function cacheResolvedService(
        string $abstract,
        mixed $instance
    ): void {
        $this->resolvedServicesCache[$abstract] = $instance;
    }

    /**
     * Retrieve the cached resolved services.
     *
     * @return array<string, mixed>
     */
    public function cacheResolvedServices(): array
    {
        return $this->resolvedServicesCache;
    }

    /**
     * Clear the resolved services cache.
     *
     * @param Closure $cacheKey
     * @return void
     */
    public function clearResolvedServicesCache(
        Closure $cacheKey
    ): void {
        if ($this->externalCache !== null) {
            $key = $this->closureToString($cacheKey);
            $this->externalCache->set($key, $this->resolvedServicesCache);
        }
        $this->resolvedServicesCache = [];
    }

    /**
     * Converts a closure to a unique string representation.
     *
     * @param Closure $closure
     * @return string
     */
    protected function closureToString(
        Closure $closure
    ): string {
        return spl_object_hash($closure);
    }

    /**
     * Load the resolved services cache from the external cache store using the provided key.
     *
     * @param string $cacheKey
     * @return void
     */
    public function loadResolvedServicesFromExternalCache(
        string $cacheKey
    ): void {
        if ($this->externalCache !== null && $this->externalCache->has($cacheKey)) {
            /** @var array<string, mixed> $cached */
            $cached = $this->externalCache->get($cacheKey);
            if (is_array($cached)) {
                $this->resolvedServicesCache = $cached;
            }
        }
    }
}
