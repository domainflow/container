<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use stdClass;
use Throwable;

#[CoversNothing]
class ContainerCircularDependencyIntegrationTest extends TestCase
{
    /**
     * @throws ContainerException|NotFoundException|Throwable
     */
    public function test_circular_dependencies_throw_a_container_exception_with_the_resolution_chain(): void
    {
        $container = new Container();

        $container->bind(A::class);
        $container->bind(B::class);
        $container->bind(C::class);
        $container->bind(D::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected: ' . A::class . ' -> ' . B::class . ' -> ' . A::class . '.');

        $container->make(A::class);
    }

    /**
     * @throws ContainerException|NotFoundException|Throwable
     */
    public function test_scoped_circular_dependencies_throw_a_container_exception(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected: ' . A::class . ' -> ' . B::class . ' -> ' . A::class . '.');

        $container->scope('request', static function (Container $scope): void {
            $scope->bind(A::class);
            $scope->bind(B::class);
            $scope->make(A::class);
        });
    }

    /**
     * @throws ContainerException|NotFoundException|Throwable
     */
    public function test_a_circular_failure_does_not_affect_another_container_instance(): void
    {
        $first = new Container();
        $first->bind(A::class);
        $first->bind(B::class);

        try {
            $first->make(A::class);
            $this->fail('Expected a circular dependency to throw.');
        } catch (ContainerException) {
            $second = new Container();

            $this->assertInstanceOf(stdClass::class, $second->make(stdClass::class));
        }
    }
}

// dummy classes
final class A
{
    public string $name;
    public int $age;
    public B $b;

    public function __construct(B $b)
    {
        $this->b = $b;
    }
}

final class B
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
