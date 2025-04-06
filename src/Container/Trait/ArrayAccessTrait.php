<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use Closure;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use DomainFlow\Container\Shared;
use InvalidArgumentException;

/**
 * Trait ArrayAccessTrait
 *
 * Provides ArrayAccess logic for the container:
 *   $container['Foo'] = new Foo();
 *   $foo = $container['Foo'];
 */
trait ArrayAccessTrait
{
    /**
     * Check if a key exists in the container.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(
        mixed $offset
    ): bool {
        return $this->has(self::keyToString($offset));
    }

    /**
     * Get a value from the container.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(
        mixed $offset
    ): mixed {
        try {
            return $this->get(self::keyToString($offset));
        } catch (ContainerException|NotFoundException) {
            return null;
        }
    }

    /**
     * Set a value in the container.
     *
     * @param mixed $offset
     * @param mixed $value
     * @throws ContainerException
     * @return void
     */
    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        $key = self::keyToString($offset);
        if ($value instanceof Shared) {
            $this->singleton($key, fn () => $value->value);
        } elseif ($value instanceof Closure) {
            $this->bind($key, $value);
        } else {
            $this->bind($key, fn () => $value);
        }
    }

    /**
     * Unset a value from the container.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(
        mixed $offset
    ): void {
        $key = self::keyToString($offset);
        unset($this->bindings[$key], $this->instances[$key]);
    }

    /**
     * Safely converts a mixed key to string.
     *
     * @param mixed $key
     *@throws InvalidArgumentException
     * @return string
     */
    public static function keyToString(
        mixed $key
    ): string {
        if (!is_scalar($key)) {
            throw new InvalidArgumentException('Key must be a scalar value.');
        }

        return (string) $key;
    }
}
