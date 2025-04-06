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
class ContainerScopedResolutionIntegrationTest extends TestCase
{
    /**
     * @throws Throwable|ContainerException|NotFoundException
     */
    public function test_scoped_instances_are_resolved_correctly(): void
    {
        $container = new Container();

        $container->scope('request', function ($scopedContainer) {
            $scopedContainer->singleton(Session::class, fn () => new Session());

            return $scopedContainer->make(Session::class);
        });

        ob_start();
        try {
            $session1 = $container->scope('request', fn ($scopedContainer) => $scopedContainer->make(Session::class));
            $session2 = $container->scope('request', fn ($scopedContainer) => $scopedContainer->make(Session::class));

            $sessionOutside = $container->make(Session::class);

            $this->assertSame($session1, $session2, "Scoped session instances should be the same.");
            $this->assertNotSame($session1, $sessionOutside, "Scoped session should be different from globally resolved session.");
        } finally {
            ob_end_clean();
        }
    }
}

// dummy class
class Session
{
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = uniqid('session_', true);
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}
