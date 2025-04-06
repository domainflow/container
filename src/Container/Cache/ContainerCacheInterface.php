<?php

declare(strict_types=1);

namespace DomainFlow\Container\Cache;

interface ContainerCacheInterface
{
    /**
     * Get the value from the cache.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Set the value in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool;

    /**
     * Check if the cache has a value for the given key.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Delete the value from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;
}
