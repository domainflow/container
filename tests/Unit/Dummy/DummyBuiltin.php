<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class with a built-in type parameter (non-optional).
 */
class DummyBuiltin
{
    public int $value;
    public function __construct(int $value)
    {
        $this->value = $value;
    }
}
