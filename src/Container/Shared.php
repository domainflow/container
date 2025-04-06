<?php

declare(strict_types=1);

namespace DomainFlow\Container;

/**
 * Wrapper to mark a binding as shared (singleton) when using array access.
 */
final class Shared
{
    public function __construct(
        public mixed $value
    ) {
    }
}
