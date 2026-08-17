<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

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
            || array_keys($cached) !== ['version', 'bindings', 'aliases']
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
                || array_keys($binding) !== ['concrete', 'shared']
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
