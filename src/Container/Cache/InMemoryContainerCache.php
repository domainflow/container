<?php

declare(strict_types=1);

namespace DomainFlow\Container\Cache;

/**
 * In-memory container cache.
 *
 * Implements ContainerCacheInterface to provide a simple in-memory cache store.
 */
final class InMemoryContainerCache implements ContainerCacheInterface
{
    /**
     * In-memory cache store.
     * @var array<string, mixed>
     */
    protected array $store = [];

    /**
     * Get the value from the cache.
     *
     * @param string $key
     * @return mixed
     */
    public function get(
        string $key
    ): mixed {
        return $this->store[$key] ?? null;
    }

    /**
     * Set the value in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set(
        string $key,
        mixed $value,
        int $ttl = 3600
    ): bool {
        $this->store[$key] = $value;

        return true;
    }

    /**
     * Check if the cache has a value for the given key.
     *
     * @param string $key
     * @return bool
     */
    public function has(
        string $key
    ): bool {
        return isset($this->store[$key]);
    }

    /**
     * Delete the value from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function delete(
        string $key
    ): bool {
        if (isset($this->store[$key])) {
            unset($this->store[$key]);
        }

        return true;
    }
}
