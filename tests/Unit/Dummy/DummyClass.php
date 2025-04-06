<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * A dummy class for testing bindings.
 */
class DummyClass
{
    public string $value;

    public function __construct(
        string $value = 'default'
    ) {
        $this->value = $value;
    }
}
