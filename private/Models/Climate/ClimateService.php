<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateClient;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Climate use cases : activating, deactivating and configuring the climate system
 * Verifies vehicle ownership before sending any command to the API
 **/
final readonly class ClimateService
{
    public function __construct(
        private ClimateClient $client,
        private VehicleRepositoryInterface $vehicleRepository,
    ) {}

    /**
     * Activates the climate system for the vehicle
     * Applies the requested cabin temperature
     *
     * @throws VehicleUnauthorizedException if the user does not own the vehicle
     **/
    public function activate(string $userId, Vin $vin, ?Temperature $temp = null): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->startClimate($vin, $temp);
    }

    /**
     * Deactivates the climate system for the vehicle
     *
     * @throws VehicleUnauthorizedException if the user does not own the vehicle
     **/
    public function deactivate(string $userId, Vin $vin): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->stopClimate($vin);
    }

    /**
     * Sets the climate keeper mode for the vehicle
     *
     * @throws VehicleUnauthorizedException if the user does not own the vehicle
     **/
    public function applyKeeperMode(string $userId, Vin $vin, KeeperMode $mode): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->setKeeperMode($vin, $mode);
    }

    /**
     * Verifies that the user owns the vehicle
     *
     * @throws VehicleUnauthorizedException if the vehicle is not accessible by the user
     **/
    private function assertOwnership(Vin $vin, string $userId): void
    {
        if (!$this->vehicleRepository->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}
