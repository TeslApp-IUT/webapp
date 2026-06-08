<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Climate use cases: CRUD on a vehicle's preconditioning schedules.
 */
final class ClimateService
{
    public function __construct(
        private readonly PreconditioningPlannerRepositoryInterface $plannerRepository,
        private readonly VehicleRepositoryInterface $vehicleRepository,
    ) {}

    /**
     * @return list<PreconditioningPlanner>
     * @throws VehicleUnauthorizedException if the user does not own the vehicle
     */
    public function listPlansForVehicle(string $userId, Vin $vin): array
    {
        $this->assertOwnership($vin, $userId);

        return $this->plannerRepository->findByVin($vin);
    }

    /**
     * @param list<DayOfWeek> $days
     * @return string the new planner id
     * @throws VehicleUnauthorizedException if the user does not own the vehicle
     */
    public function createPlan(
        string $userId,
        Vin $vin,
        string $activationHour,
        array $days,
        bool $memorizeLongTerm,
    ): string {
        $this->assertOwnership($vin, $userId);

        $planner = new PreconditioningPlanner(
            id: null,
            vin: $vin,
            activationHour: $activationHour,
            deactivateAfterSuccess: !$memorizeLongTerm,
            days: $days,
        );

        return $this->plannerRepository->save($planner);
    }

    /**
     * @param list<DayOfWeek> $days
     * @throws VehicleUnauthorizedException if the vehicle isn't the user's, or the planner isn't the vehicle's
     */
    public function updatePlan(
        string $userId,
        Vin $vin,
        string $planId,
        string $activationHour,
        array $days,
        bool $memorizeLongTerm,
    ): void {
        $this->assertOwnership($vin, $userId);
        $this->assertPlannerBelongsToVehicle($planId, $vin, $userId);

        $planner = new PreconditioningPlanner(
            id: $planId,
            vin: $vin,
            activationHour: $activationHour,
            deactivateAfterSuccess: !$memorizeLongTerm,
            days: $days,
        );

        $this->plannerRepository->update($planner);
    }

    /** @throws VehicleUnauthorizedException if the vehicle or planner isn't the user's */
    public function deletePlan(string $userId, Vin $vin, string $planId): void
    {
        $this->assertOwnership($vin, $userId);
        $this->assertPlannerBelongsToVehicle($planId, $vin, $userId);

        $this->plannerRepository->deleteById($planId);
    }

    /** @throws VehicleUnauthorizedException */
    private function assertOwnership(Vin $vin, string $userId): void
    {
        if (!$this->vehicleRepository->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }

    /** @throws VehicleUnauthorizedException */
    private function assertPlannerBelongsToVehicle(string $planId, Vin $vin, string $userId): void
    {
        $planner = $this->plannerRepository->findById($planId);

        if ($planner === null || $planner->vin->value !== $vin->value) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}
