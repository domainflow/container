<?php

declare(strict_types=1);

namespace DomainFlow\Container;

use DomainFlow\Container;

/**
 * Builder for contextual bindings.
 */
class ContextualBindingBuilder
{
    /**
     * Create a new ContextualBindingBuilder instance.
     *
     * @param Container $container
     * @param string $concrete
     */
    public function __construct(
        protected Container $container,
        protected string $concrete
    ) {
    }

    /**
     * Specify which dependency (abstract) to override in the given concrete class.
     *
     * @param string $abstract
     * @return NeedsBindingBuilder
     */
    public function needs(
        string $abstract
    ): NeedsBindingBuilder {
        return $this->createNeedsBindingBuilder($abstract);
    }

    /**
     * Factory method for creating a NeedsBindingBuilder.
     *
     * This method can be overridden to customize or stub out the builder in tests.
     *
     * @param string $abstract
     * @return NeedsBindingBuilder
     */
    protected function createNeedsBindingBuilder(
        string $abstract
    ): NeedsBindingBuilder {
        return new NeedsBindingBuilder(
            $this->container,
            $this->concrete,
            $abstract
        );
    }
}
