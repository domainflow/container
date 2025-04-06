<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyVariadicConstructor
{
    public array $values;
    public function __construct(...$values)
    {
        $this->values = $values;
    }
}
