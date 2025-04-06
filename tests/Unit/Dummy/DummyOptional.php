<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class with an optional parameter.
 */
class DummyOptional
{
    public int $value;
    public function __construct(int $value = 42)
    {
        $this->value = $value;
    }
}
