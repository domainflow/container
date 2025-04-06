<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Trait;

use DomainFlow\Container;
use DomainFlow\Container\Trait\TagManagerTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use stdClass;

#[CoversClass(Container::class)]
final class TagManagerTraitTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new class() extends Container {
            use TagManagerTrait;

            public function addMockService(
                string $id,
                mixed $instance
            ): void {
                $this->instances[$id] = $instance;
            }
        };
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

    public function test_tag_adds_services_to_tag_list_whiteBox(): void
    {
        $this->container->tag('my_tag', ['ServiceA', 'ServiceB']);

        $reflection = new ReflectionClass($this->container);
        $prop = $reflection->getProperty('tags');
        $tagsValue = $prop->getValue($this->container);

        $this->assertArrayHasKey('my_tag', $tagsValue);
        $this->assertContains('ServiceA', $tagsValue['my_tag']);
        $this->assertContains('ServiceB', $tagsValue['my_tag']);
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function test_getByTag_returns_services(): void
    {
        $serviceA = new stdClass();
        $serviceB = new stdClass();

        $this->container->addMockService('ServiceA', $serviceA);
        $this->container->addMockService('ServiceB', $serviceB);

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
