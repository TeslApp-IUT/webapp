<?php

declare(strict_types=1);

namespace Teslapp\Models\Vehicle;

use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Vehicle use cases: syncing the user's vehicles with the Tesla API, and listing them.
 */
final class VehicleService
{
    public function __construct(
        private readonly VehicleStateClient $teslaApi,
        private readonly VehicleRepositoryInterface $vehicleRepository,
    ) {}

    /**
     * Reconciles the user's vehicles with the Tesla API: a VIN present in the API
     * but missing in database is inserted; a VIN stored in database but gone from
     * the API is removed (the API is the source of truth).
     */
    public function syncUserVehicles(string $userId): void
    {
        $apiVehicles = $this->teslaApi->listVehicles();
        $dbVehicles = $this->vehicleRepository->findByUser($userId);

        $dbVins = array_map(static fn(Vehicle $v): string => $v->vin->value, $dbVehicles);
        $apiVins = array_map(static fn(Vehicle $v): string => $v->vin->value, $apiVehicles);

        $newVehicles = array_filter(
            $apiVehicles,
            static fn(Vehicle $v): bool => !in_array($v->vin->value, $dbVins, true),
        );

        if ($newVehicles !== []) {
            foreach ($newVehicles as $apiVehicle) {
                $this->vehicleRepository->save(
                    new Vehicle(
                        vin: $apiVehicle->vin,
                        userId: $userId,
                        name: $apiVehicle->name,
                        modelCode: $this->modelCodeForVin($apiVehicle->vin),
                    ),
                );
            }
        }

        foreach ($dbVehicles as $dbVehicle) {
            if (!in_array($dbVehicle->vin->value, $apiVins, true)) {
                $this->vehicleRepository->detachByVin($dbVehicle->vin);
            }
        }
    }

    /** @return Vehicle[] */
    public function listForUser(string $userId): array
    {
        return $this->vehicleRepository->findByUser($userId);
    }

    /** @return array<string, VehicleConnectivityStatus> VIN => live status */
    public function connectivityForUser(): array
    {
        return $this->teslaApi->fetchConnectivity();
    }

    /** Display name of the model line, with a safe fallback. */
    public function modelNameForVin(Vin $vin): string
    {
        return $this->modelLineFromVin($vin) ?? 'Tesla';
    }

    /**
     * The model code persisted on the vehicle: the 4th VIN character itself
     * (matches vehicle_models.vin_code). Throws on an unknown Tesla model line.
     */
    private function modelCodeForVin(Vin $vin): string
    {
        if ($this->modelLineFromVin($vin) === null) {
            throw new \InvalidArgumentException("Unknown Tesla model for VIN $vin->value");
        }

        return $vin->value[3];
    }

    /** The 4th VIN character encodes the Tesla model line. */
    private function modelLineFromVin(Vin $vin): ?string
    {
        return match ($vin->value[3]) {
            '3' => 'Model 3',
            'Y' => 'Model Y',
            'S' => 'Model S',
            'X' => 'Model X',
            'C' => 'Cybertruck',
            default => null,
        };
    }
}
