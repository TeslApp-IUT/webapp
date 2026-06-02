<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * In-house dependencies injection container.
 *
 * Stores recipes (callables) that can build each service, and
 * caches the instance produced to share it (only one copy per
 * identifier, singleton-style). The wiring is explicit—no autowiring
 * by reflection—what keeps the dependencies visible and defensible.
 *
 * Revenue is reported in private/config/container.php.
 *
 * @package Teslapp\Utils
 */
final class Container
{
    /**
     * Build revenue, indexed by service ID.
     *
     * @var array<string, callable(self): object>
     */
    private array $recipes = [];

    /**
     * Instances already built, kept for sharing.
     *
     * @var array<string, object>
     */
    private array $shared = [];

    /**
     * Saves a service build recipe.
     *
     * @param string $id Service ID (typically an FQCN via ::class)
     * @param callable(self): object $recipe Fabrique receiving container and returning instance
     * @return void
     */
    public function set(string $id, callable $recipe): void
    {
        $this->recipes[$id] = $recipe;
    }

    /**
     * Resolves a service, reusing the already built instance if it exists.
     *
     * @param string $id Service ID
     * @return object The shared instance of the service
     * @throws ServiceNotFoundException If no recipe is logged for this ID
     */
    public function get(string $id): object
    {
        return $this->shared[$id] ??= $this->build($id);
    }

    /**
     * Indicates whether the container knows a service (either a saved receipt or an already built instance).
     *
     * @param string $id Service ID
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->recipes[$id]) || isset($this->shared[$id]);
    }

    /**
     * Creates an instance from its recipe.
     *
     * @param string $id Service ID
     * @return object
     * @throws ServiceNotFoundException If no recipe is registered for this ID
     */
    private function build(string $id): object
    {
        if (!isset($this->recipes[$id])) {
            throw new ServiceNotFoundException("Service isn't registered in the container: {$id}");
        }

        return $this->recipes[$id]($this);
    }
}
