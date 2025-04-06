<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerCircularDependencyIntegrationTest extends TestCase
{
    /**
     * @throws ContainerException|NotFoundException|Throwable
     */
    public function test_circular_dependencies_are_resolved_correctly(): void
    {
        $container = new Container();

        $container->bind(A::class);
        $container->bind(B::class);
        $container->bind(C::class);
        $container->bind(D::class);

        ob_start();
        try {
            $aInstance = $container->make(A::class);
            $bInstance = $container->make(B::class);
            $cInstance = $container->make(C::class);
            $dInstance = $container->make(D::class);
        } finally {
            ob_end_clean();
        }

        $this->assertInstanceOf(B::class, $aInstance->b, "A should contain an instance of B.");
        $this->assertInstanceOf(A::class, $bInstance->a, "B should contain an instance of A.");
        $this->assertInstanceOf(A::class, $cInstance->a, "C should contain an instance of A.");
        $this->assertInstanceOf(B::class, $dInstance->b, "D should contain an instance of B.");
    }
}

// dummy classes
class A
{
    public string $name;
    public int $age;
    public B $b;

    public function __construct(B $b)
    {
        $this->b = $b;
    }
}

class B
{
    public string $category;
    public float $price;
    public A $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}

class C
{
    public bool $isActive;
    public array $data;
    public A $a;

    public function __construct(A $a)
    {
        $this->a = $a;
    }
}

class D
{
    public string $status;
    public ?B $b;

    public function __construct(B $b)
    {
        $this->b = $b;
    }
}
