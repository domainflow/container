<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use stdClass;

#[CoversClass(Container::class)]
final class TagManagerTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function test_tag_adds_services_to_tag_list(): void
    {
        $this->container->tag('my_tag', ['ServiceA', 'ServiceB']);

        $services = $this->container->getByTag('my_tag');

        $this->assertEmpty(
            $services,
            "Because 'ServiceA' and 'ServiceB' aren't in the container, getByTag() returns empty"
        );
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function test_getByTag_returns_services(): void
    {
        $serviceA = new stdClass();
        $serviceB = new stdClass();

        $this->container->instance('ServiceA', $serviceA);
        $this->container->instance('ServiceB', $serviceB);

        $this->container->tag('my_tag', ['ServiceA', 'ServiceB']);

        $result = $this->container->getByTag('my_tag');

        $this->assertCount(2, $result);
        $this->assertSame($serviceA, $result['ServiceA']);
        $this->assertSame($serviceB, $result['ServiceB']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function test_getByTag_returns_empty_array_when_tag_does_not_exist(): void
    {
        $result = $this->container->getByTag('non_existent_tag');

        $this->assertEmpty($result);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function test_getByTag_skips_unregistered_services(): void
    {
        $this->container->tag('my_tag', ['ServiceA', 'ServiceB']);
        $result = $this->container->getByTag('my_tag');

        $this->assertEmpty($result);
    }

}
