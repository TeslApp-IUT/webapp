<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Shared\ValueObjects\Vin;

interface PreconditioningPlannerRepositoryInterface
{
    /** @return list<PreconditioningPlanner> */
    public function findByVin(Vin $vin): array;

    public function findById(string $id): ?PreconditioningPlanner;

    /** @return string the generated planner id (UUID) */
    public function save(PreconditioningPlanner $planner): string;

    public function update(PreconditioningPlanner $planner): void;

    public function deleteById(string $id): void;
}
