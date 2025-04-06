<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

/**
 * Trait CircularDependencyResolverTrait
 *
 * Provides a lazy proxy for resolving circular dependencies.
 */
trait CircularDependencyResolverTrait
{
    /**
     * Resolve a circular dependency for the given abstract.
     *
     * This method returns a callable proxy which, when invoked, returns the resolved instance.
     *
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @return object
     */
    protected function resolveCircularDependency(string $abstract, array $parameters = []): object
    {
        $proxyClassName = '__Proxy_' . str_replace('\\', '_', $abstract);

        if (!class_exists($proxyClassName)) {
            eval("
                class $proxyClassName extends \\$abstract {
                    private \$resolver;
                    private ?object \$realInstance = null;
                    
                    public function __construct(string \$class, array \$parameters) {
                        \$this->resolver = function() use (\$class, \$parameters) {
                            return Container::getInstance()->make(\$class, \$parameters);
                        };
                    }
                    
                    private function getRealInstance() {
                        if (\$this->realInstance === null) {
                            \$this->realInstance = (\$this->resolver)();
                        }
                        return \$this->realInstance;
                    }
    
                    public function __call(\$method, \$args) {
                        return \$this->getRealInstance()->\$method(...\$args);
                    }
    
                    public function __get(\$property) {
                        return \$this->getRealInstance()->\$property;
                    }
    
                    public function __set(\$property, \$value) {
                        \$this->getRealInstance()->\$property = \$value;
                    }
                }
            ");
        }

        return new $proxyClassName($abstract, $parameters);
    }
}
