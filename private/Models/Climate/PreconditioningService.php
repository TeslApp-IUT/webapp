<?php

declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateCommandClient;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Preconditioning use cases: CRUD on a vehicle's schedules.
 */
final class PreconditioningService
{
    public function __construct(
        private readonly PreconditioningPlannerRepositoryInterface $plannerRepository,
        private readonly VehicleRepositoryInterface $vehicleRepository,
        private readonly ClimateCommandClient $climateCommands,
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
        bool $enabled,
        ?GeoPoint $location = null,
        ?string $locationLabel = null,
    ): string {
        $this->assertOwnership($vin, $userId);

        $planner = new PreconditioningPlanner(
            id: null,
            vin: $vin,
            activationHour: $activationHour,
            deactivateAfterSuccess: !$memorizeLongTerm,
            days: $days,
            enabled: $enabled,
            location: $location,
            locationLabel: $locationLabel,
        );

        $id = $this->plannerRepository->save($planner);
        $this->pushAndStore($id, $planner);

        return $id;
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
        bool $enabled,
        ?GeoPoint $location = null,
        ?string $locationLabel = null,
    ): void {
        $existing = $this->requireOwnedPlanner($userId, $vin, $planId);

        $planner = new PreconditioningPlanner(
            id: $planId,
            vin: $vin,
            activationHour: $activationHour,
            deactivateAfterSuccess: !$memorizeLongTerm,
            days: $days,
            enabled: $enabled,
            location: $location,
            locationLabel: $locationLabel,
            teslaScheduleId: $existing->teslaScheduleId,
        );

        $this->plannerRepository->update($planner);
        $this->pushAndStore($planId, $planner);
    }

    /** @throws VehicleUnauthorizedException if the vehicle or planner isn't the user's */
    public function deletePlan(string $userId, Vin $vin, string $planId): void
    {
        $existing = $this->requireOwnedPlanner($userId, $vin, $planId);

        if ($existing->teslaScheduleId !== null) {
            $this->climateCommands->removePreconditionSchedule($vin, $existing->teslaScheduleId);
        }

        $this->plannerRepository->deleteById($planId);
    }

    /**
     * Enables or disables a schedule from the list, without opening the edit form.
     * A disabled schedule stays listed and can be re-enabled.
     *
     * @throws VehicleUnauthorizedException if the vehicle or planner isn't the user's
     */
    public function setPlanEnabled(string $userId, Vin $vin, string $planId, bool $enabled): void
    {
        $this->requireOwnedPlanner($userId, $vin, $planId);

        $this->plannerRepository->setEnabled($planId, $enabled);
    }

    /**
     * Sends the schedule to the car and stores the returned Tesla id, so a later edit or
     * delete can target the right schedule. Skipped without a geofence; no-op while dry-run is on.
     */
    private function pushAndStore(string $plannerId, PreconditioningPlanner $planner): void
    {
        if ($planner->location === null) {
            return;
        }

        $teslaScheduleId = $this->climateCommands->addPreconditionSchedule(
            $planner->vin,
            $planner->preconditionTimeMinutes(),
            $planner->daysOfWeekCsv(),
            $planner->enabled,
            $planner->deactivateAfterSuccess,
            $planner->location->latitude,
            $planner->location->longitude,
            $planner->teslaScheduleId,
        );

        if ($teslaScheduleId !== null) {
            $this->plannerRepository->setTeslaScheduleId($plannerId, $teslaScheduleId);
        }
    }

    /** @throws VehicleUnauthorizedException if the vehicle or planner isn't the user's */
    private function requireOwnedPlanner(
        string $userId,
        Vin $vin,
        string $planId,
    ): PreconditioningPlanner {
        $this->assertOwnership($vin, $userId);

        $planner = $this->plannerRepository->findById($planId);
        if ($planner === null || $planner->vin->value !== $vin->value) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }

        return $planner;
    }

    /** @throws VehicleUnauthorizedException */
    private function assertOwnership(Vin $vin, string $userId): void
    {
        if (!$this->vehicleRepository->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}
