<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container\Cache\ContainerCacheInterface;

/**
 * Trait CacheManagerTrait
 *
 * Persists validated, declarative class bindings for a cold container.
 */
trait CacheManagerTrait
{
    /**
     * External cache store (optional).
     *
     * @var ContainerCacheInterface|null
     */
    protected ?ContainerCacheInterface $externalCache = null;

    /**
     * Legacy process-local resolved-service cache.
     *
     * @var array<string, mixed>
     */
    protected array $resolvedServicesCache = [];

    /**
     * Prevents hydrated definitions from being written back while loading.
     */
    private bool $hydratingCachedDefinitions = false;

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
        $this->hydrateCachedDefinitions();
    }

    /**
     * Delete externally stored definitions. This does not alter the current
     * container's registrations or retained instances.
     */
    public function clearResolutionCache(): void
    {
        $this->externalCache?->delete(ContainerCacheInterface::DEFINITION_CACHE_KEY);
    }

    /**
     * Cache a resolved service for the legacy resolution-cache API.
     *
     * @deprecated since 0.2.0. Use a shared binding for process-local reuse.
     * @param string $abstract
     * @param mixed $instance
     */
    public function cacheResolvedService(string $abstract, mixed $instance): void
    {
        $this->resolvedServicesCache[$abstract] = $instance;
    }

    /**
     * Return services cached by the legacy resolution-cache API.
     *
     * @deprecated since 0.2.0. Use explicit bindings and instances instead.
     * @return array<string, mixed>
     */
    public function cacheResolvedServices(): array
    {
        return $this->resolvedServicesCache;
    }

    /**
     * Persist and clear legacy resolved-service values under a closure-derived key.
     *
     * @deprecated since 0.2.0. Store application data in an application cache;
     *             ContainerCacheInterface is otherwise reserved for declarative definitions.
     */
    public function clearResolvedServicesCache(Closure $cacheKey): void
    {
        if ($this->externalCache !== null) {
            $this->externalCache->set(spl_object_hash($cacheKey), $this->resolvedServicesCache);
        }

        $this->resolvedServicesCache = [];
    }

    /**
     * Restore legacy resolved-service values from an external cache key.
     *
     * @deprecated since 0.2.0. Store application data in an application cache;
     *             ContainerCacheInterface is otherwise reserved for declarative definitions.
     */
    public function loadResolvedServicesFromExternalCache(string $cacheKey): void
    {
        if ($this->externalCache === null || !$this->externalCache->has($cacheKey)) {
            return;
        }

        $cached = $this->normalizeResolvedServicesCache($this->externalCache->get($cacheKey));
        if ($cached !== null) {
            $this->resolvedServicesCache = $cached;
        }
    }

    /**
     * @param mixed $values
     * @return array<string, mixed>|null
     */
    private function normalizeResolvedServicesCache(mixed $values): ?array
    {
        if (!is_array($values)) {
            return null;
        }

        $normalized = [];
        foreach ($values as $key => $_) {
            if (!is_string($key)) {
                return null;
            }
            $normalized[$key] = $_;
        }

        return $normalized;
    }

    /**
     * Persist the cacheable part of the current container definition.
     */
    protected function persistCachedDefinitions(): void
    {
        if ($this->externalCache === null || $this->hydratingCachedDefinitions) {
            return;
        }

        $this->externalCache->set(ContainerCacheInterface::DEFINITION_CACHE_KEY, [
            'version' => 1,
            'bindings' => $this->cacheableBindings,
            'aliases' => $this->cacheableAliases(),
        ], 0);
    }

    /**
     * Import only valid class-string bindings and aliases into an empty
     * container. Cached closures, instances, and arbitrary values are never
     * accepted because they cannot be safely reconstructed across processes.
     */
    private function hydrateCachedDefinitions(): void
    {
        if ($this->bindings !== [] || $this->instances !== [] || $this->aliases !== []) {
            return;
        }

        $cached = $this->externalCache?->get(ContainerCacheInterface::DEFINITION_CACHE_KEY);
        if (!is_array($cached) || !$this->isValidCachedDefinitions($cached)) {
            return;
        }

        /** @var array<string, array{concrete: class-string, shared: bool}> $bindings */
        $bindings = $cached['bindings'];
        /** @var array<string, string> $aliases */
        $aliases = $cached['aliases'];

        $this->hydratingCachedDefinitions = true;
        try {
            foreach ($bindings as $abstract => $binding) {
                $this->bind($abstract, $binding['concrete'], $binding['shared']);
            }

            foreach ($aliases as $alias => $abstract) {
                $this->alias($abstract, $alias);
            }
        } finally {
            $this->hydratingCachedDefinitions = false;
        }
    }

    /**
     * @param mixed $cached
     */
    private function isValidCachedDefinitions(mixed $cached): bool
    {
        if (!is_array($cached)
            || ($cached['version'] ?? null) !== 1
            || array_diff(array_keys($cached), ['version', 'bindings', 'aliases']) !== []
            || count($cached) !== 3
            || !isset($cached['bindings'], $cached['aliases'])
            || !is_array($cached['bindings'])
            || !is_array($cached['aliases'])
        ) {
            return false;
        }

        foreach ($cached['bindings'] as $abstract => $binding) {
            if (!is_string($abstract)
                || $abstract === ''
                || !is_array($binding)
                || array_diff(array_keys($binding), ['concrete', 'shared']) !== []
                || count($binding) !== 2
                || !isset($binding['concrete'], $binding['shared'])
                || !is_string($binding['concrete'])
                || !class_exists($binding['concrete'])
                || !is_bool($binding['shared'])
            ) {
                return false;
            }
        }

        foreach ($cached['aliases'] as $alias => $abstract) {
            if (!is_string($alias)
                || $alias === ''
                || !is_string($abstract)
                || $abstract === ''
                || (!array_key_exists($abstract, $cached['bindings']) && !class_exists($abstract))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function cacheableAliases(): array
    {
        return array_filter(
            $this->aliases,
            fn (string $abstract): bool => $this->isCacheableAliasTarget($abstract)
        );
    }

    private function isCacheableAliasTarget(string $abstract): bool
    {
        if (array_key_exists($abstract, $this->bindings) || array_key_exists($abstract, $this->instances)) {
            return array_key_exists($abstract, $this->cacheableBindings);
        }

        return class_exists($abstract);
    }
}
