<?php

declare(strict_types=1);

namespace Teslapp\Utils;

/**
 * Conteneur d'injection de dépendances maison.
 *
 * Stocke des « recettes » (callables) qui savent construire chaque service, et
 * met en cache l'instance produite pour la partager (un seul exemplaire par
 * identifiant, façon singleton). Le câblage est explicite — pas d'autowiring
 * par réflexion — ce qui garde les dépendances visibles et défendables.
 *
 * Les recettes sont déclarées dans private/config/container.php.
 *
 * @package Teslapp\Utils
 */
final class Container
{
    /**
     * Recettes de construction, indexées par identifiant de service.
     *
     * @var array<string, callable(self): object>
     */
    private array $recipes = [];

    /**
     * Instances déjà construites, conservées pour être partagées.
     *
     * @var array<string, object>
     */
    private array $shared = [];

    /**
     * Enregistre la recette de construction d'un service.
     *
     * @param string $id Identifiant du service (typiquement un FQCN via ::class)
     * @param callable(self): object $recipe Fabrique recevant le conteneur et retournant l'instance
     * @return void
     */
    public function set(string $id, callable $recipe): void
    {
        $this->recipes[$id] = $recipe;
    }

    /**
     * Résout un service, en réutilisant l'instance déjà construite si elle existe.
     *
     * @param string $id Identifiant du service
     * @return object L'instance partagée du service
     * @throws ServiceNotFoundException Si aucune recette n'est enregistrée pour cet identifiant
     */
    public function get(string $id): object
    {
        return $this->shared[$id] ??= $this->build($id);
    }

    /**
     * Indique si le conteneur connaît un service (recette enregistrée ou instance déjà construite).
     *
     * @param string $id Identifiant du service
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->recipes[$id]) || isset($this->shared[$id]);
    }

    /**
     * Construit une instance à partir de sa recette.
     *
     * @param string $id Identifiant du service
     * @return object
     * @throws ServiceNotFoundException Si aucune recette n'est enregistrée pour cet identifiant
     */
    private function build(string $id): object
    {
        if (!isset($this->recipes[$id]))
        {
            throw new ServiceNotFoundException("Service non enregistré dans le conteneur : {$id}");
        }

        return $this->recipes[$id]($this);
    }
}
