<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

/**
 * Trait ContextualBindingTrait
 *
 * Provides methods for setting contextual bindings.
 *
 * Format: [Concrete => [Abstract => Implementation]]
 */
trait ContextualBindingTrait
{
    /**
     * Add a contextual binding.
     *
     * @param string $concrete
     * @param string $abstract
     * @param string $implementation
     * @return void
     */
    public function addContextualBinding(
        string $concrete,
        string $abstract,
        string $implementation
    ): void {
        if (!isset($this->contextual[$concrete])) {
            $this->contextual[$concrete] = [];
        }
        $this->contextual[$concrete][$abstract] = $implementation;
    }
}
