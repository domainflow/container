<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyWithUnionContextual
{
    public $dependency;
    public function __construct(DummyUnionA|DummyUnionB $dependency)
    {
        $this->dependency = $dependency;
    }
}
