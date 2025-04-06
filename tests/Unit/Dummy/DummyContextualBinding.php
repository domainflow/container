<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

use DomainFlow\Container\Trait\ContextualBindingTrait;

/**
 * Dummy class for testing ContextualBindingTrait.
 */
class DummyContextualBinding
{
    use ContextualBindingTrait;

    /**
     * Contextual bindings storage.
     *
     * @var array<string, array<string, string>>
     */
    public array $contextual = [];
}
