<?php
declare(strict_types=1);

namespace Teslapp\Models\Climate;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateClient;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

final readonly class ClimateService
{
    public function __construct(
        private ClimateClient $client,
        private VehicleRepositoryInterface $vehicleRepository,
    ) {}

    public function activate(string $userId, Vin $vin, ?Temperature $temp = null): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->startClimate($vin, $temp);
    }

    public function deactivate(string $userId, Vin $vin): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->stopClimate($vin);
    }

    public function applyKeeperMode(string $userId, Vin $vin, KeeperMode $mode): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->setKeeperMode($vin, $mode);
    }

    /** @throws VehicleUnauthorizedException */
    private function assertOwnership(Vin $vin, string $userId): void
    {
        if (!$this->vehicleRepository->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}