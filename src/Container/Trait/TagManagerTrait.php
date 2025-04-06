<?php

declare(strict_types=1);

namespace DomainFlow\Container\Trait;

use DomainFlow\Container\Exception\NotFoundException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Trait TagManagerTrait
 *
 * Provides methods for tagging services and retrieving them by tag.
 */
trait TagManagerTrait
{
    /**
     * @var array<string, list<string>>
     */
    protected array $tags = [];

    /**
     * Tag multiple services with a given tag.
     *
     * @param string $tag
     * @param list<string> $services
     * @return void
     */
    public function tag(
        string $tag,
        array $services
    ): void {
        if (!isset($this->tags[$tag])) {
            $this->tags[$tag] = [];
        }
        foreach ($services as $service) {
            $this->tags[$tag][] = $service;
        }
    }

    /**
     * Retrieve all services that have been tagged with a given tag.
     *
     * @param string $tag
     * @throws ContainerExceptionInterface
     * @return array<string, mixed>
     */
    public function getByTag(
        string $tag
    ): array {
        $results = [];
        if (!isset($this->tags[$tag])) {
            return $results;
        }
        foreach ($this->tags[$tag] as $service) {
            try {
                $results[$service] = $this->get($service);
            } catch (NotFoundException) {
                continue;
            }
        }

        return $results;
    }
}
