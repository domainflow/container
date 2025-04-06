<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Unit\Container\Cache;

use DomainFlow\Container\Cache\InMemoryContainerCache;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryContainerCache::class)]
final class InMemoryContainerCacheTest extends TestCase
{
    private InMemoryContainerCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryContainerCache();
    }

    public function test_set_and_get_value(): void
    {
        $key = 'testKey';
        $value = 'testValue';

        $this->assertTrue($this->cache->set($key, $value));
        $this->assertSame($value, $this->cache->get($key));

        $this->assertTrue($this->cache->set('anotherKey', 'anotherValue', 1000));
        $this->assertSame('anotherValue', $this->cache->get('anotherKey'));
    }

    public function test_get_returns_null_for_nonexistent_key(): void
    {
        $this->assertNull($this->cache->get('nonexistent'));
    }

    public function test_has_returns_true_if_key_exists(): void
    {
        $key = 'exists';
        $this->cache->set($key, 'value');
        $this->assertTrue($this->cache->has($key));
    }

    public function test_has_returns_false_if_key_does_not_exist(): void
    {
        $this->assertFalse($this->cache->has('missing'));
    }

    public function test_delete_removes_key(): void
    {
        $key = 'toDelete';
        $this->cache->set($key, 'value');
        $this->assertTrue($this->cache->has($key));

        $this->assertTrue($this->cache->delete($key));
        $this->assertFalse($this->cache->has($key));
        $this->assertNull($this->cache->get($key));
    }

    public function test_delete_returns_true_even_if_key_not_exist(): void
    {
        $this->assertTrue($this->cache->delete('nonexistent'));
    }
}
