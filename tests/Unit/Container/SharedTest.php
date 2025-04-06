<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container;

use DomainFlow\Container\Shared;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Shared::class)]
final class SharedTest extends TestCase
{
    public function test_constructor_sets_value(): void
    {
        $value = 'my_shared_value';
        $shared = new Shared($value);
        $this->assertSame($value, $shared->value);
    }
}
