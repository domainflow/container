<?php

declare(strict_types=1);

namespace DomainFlow\Container;

use DomainFlow\Container;

/**
 * Helper class to bind a concrete class to an abstract class.
 */
class NeedsBindingBuilder
{
    /**
     * Create a new NeedsBindingBuilder instance.
     *
     * @param Container $container
     * @param string $concrete
     * @param string $abstract
     */
    public function __construct(
        protected Container $container,
        protected string $concrete,
        protected string $abstract
    ) {
    }

    /**
     * Specify the implementation that should be used to resolve the dependency.
     *
     * @param string $implementation
     * @return void
     */
    public function give(
        string $implementation
    ): void {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->abstract,
            $implementation
        );
    }
}
