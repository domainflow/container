<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use Throwable;

/**
 * Trait PsrContainerTrait
 *
 * Provides PSR-11 compatible methods get() and has().
 */
trait PsrContainerTrait
{
    /**
     * Retrieve an entry from the container by its identifier.
     *
     * @param string $id
     * @throws NotFoundException|ContainerException
     * @return mixed
     */
    public function get(
        string $id
    ): mixed {
        try {
            return $this->make($id);
        } catch (NotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ContainerException(
                "Error while retrieving the entry '$id': " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Check if the container can return an entry for the given identifier.
     *
     * @param string $id
     * @return bool
     */
    public function has(
        string $id
    ): bool {
        $id = $this->aliases[$id] ?? $id;

        return isset($this->bindings[$id])
            || isset($this->instances[$id])
            || class_exists($id);
    }
}
