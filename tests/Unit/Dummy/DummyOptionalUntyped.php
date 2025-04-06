<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Dummy;

/**
 * Dummy class for testing.
 */
class DummyOptionalUntyped
{
    public $param;
    public function __construct($param = 'defaultUntyped')
    {
        $this->param = $param;
    }
}
