<?php

declare(strict_types=1);

namespace Teslapp\Models\Charging;

use Teslapp\Models\Shared\ValueObjects\Vin;

interface ChargingPlannerRepositoryInterface
{
    /** @return list<ChargingPlanner> */
    public function findByVin(Vin $vin): array;

    public function findById(string $id): ?ChargingPlanner;

    /** @return string the generated planner id (UUID) */
    public function save(ChargingPlanner $planner): string;

    public function update(ChargingPlanner $planner): void;

    public function setEnabled(string $id, bool $enabled): void;

    public function setTeslaScheduleId(string $id, int $teslaScheduleId): void;

    public function deleteById(string $id): void;
}
