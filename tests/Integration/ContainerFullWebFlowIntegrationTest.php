<?php

declare(strict_types=1);

namespace DomainFlow\Tests\Integration;

use DomainFlow\Container;
use DomainFlow\Container\Attribute\Inject;
use DomainFlow\Container\Exception\ContainerException;
use DomainFlow\Container\Exception\NotFoundException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Throwable;

#[CoversNothing]
class ContainerFullWebFlowIntegrationTest extends TestCase
{
    /**
     * @throws ContainerException|Throwable|NotFoundException
     */
    public function test_full_web_flow_executes_correctly(): void
    {
        $this->expectOutputRegex('/.*/');

        $container = new Container();

        $container->singleton('database', fn () => new Database());
        $container->bind(LoggerInterfaceF::class, ConsoleLoggerC::class);

        $container->addBeforeResolve(function (string $concrete, array $params) {
            if (!getenv('DISABLE_TEST_LOGS')) {
                echo "🔄 Resolving: {$concrete}" . PHP_EOL;
            }
        });

        $container->addAfterResolve(function (object $instance, string $concrete, array $params) {
            if (!getenv('DISABLE_TEST_LOGS')) {
                echo "Resolved: {$concrete}" . PHP_EOL;
            }

            return null;
        });

        $container->scope('request', function (Container $scoped) {
            $scoped->instance(Request::class, new Request(['auth' => 1]));
        });

        $request = $container->scope('request', fn (Container $scoped) => $scoped->get(Request::class));

        $middleware = new AuthMiddleware();

        ob_start();
        try {
            $finalAction = function (Request $req) use ($container) {
                $controller = $container->make(UserController::class);
                $container->call([$controller, 'getUser'], ['request' => $req]);
            };

            $container->call([$middleware, 'handle'], [
                'request' => $request,
                'next' => $finalAction,
            ]);
        } finally {
            $output = ob_get_clean();
        }

        $this->assertSame($request, $container->scope('request', fn ($scoped) => $scoped->get(Request::class)), "Request should remain the same within the request scope.");

        $expectedOrder = [
            "[Middleware] Authenticated request",
            "[Logger] Fetching user...",
            "[UserController] Connected to DB:",
            "[UserController] Returning user: John Doe",
        ];

        foreach ($expectedOrder as $snippet) {
            $this->assertStringContainsString($snippet, $output);
        }

        $db1 = $container->make('database');
        $db2 = $container->make('database');
        $this->assertSame($db1, $db2, "Database should be a singleton globally.");

        $this->assertStringContainsString("[Middleware] Authenticated request", $output);
        $this->assertStringContainsString("[Logger] Fetching user...", $output);
        $this->assertStringContainsString("[UserController] Connected to DB:", $output);
        $this->assertStringContainsString("[UserController] Returning user: John Doe", $output);
    }

}

// dummy classes and interfaces
class Request
{
    public function __construct(
        public array $queryParams = []
    ) {
    }
}

class AuthMiddleware
{
    public function handle(
        Request $request,
        callable $next
    ) {
        if (!isset($request->queryParams['auth'])) {
            die("[Middleware] Unauthorized request! Add ?auth=1 to URL." . PHP_EOL);
        }
        echo "[Middleware] Authenticated request" . PHP_EOL;

        return $next($request);
    }
}

interface LoggerInterfaceF
{
    public function log(string $message): void;
}

class ConsoleLoggerC implements LoggerInterfaceF
{
    public function log(
        string $message
    ): void {
        echo "[Logger] " . $message . PHP_EOL;
    }
}

class Database
{
    private string $connectionId;
    public function __construct()
    {
        $this->connectionId = uniqid('db_', true);
    }
    public function getConnectionId(): string
    {
        return $this->connectionId;
    }
}

class UserController
{
    #[Inject]
    private LoggerInterfaceF $logger;

    #[Inject]
    private Database $db;

    public function getUser(
        Request $request
    ): void {
        $this->logger->log("Fetching user...");
        echo "[UserController] Connected to DB: " . $this->db->getConnectionId() . PHP_EOL;
        echo "[UserController] Returning user: John Doe" . PHP_EOL;
    }
}
