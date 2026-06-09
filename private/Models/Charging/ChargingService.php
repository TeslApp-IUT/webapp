<?php

declare(strict_types=1);

namespace Teslapp\Models\Charging;

use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ChargingCommandClient;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

/**
 * Immediate charging use cases: start/stop the charge, tune its limit and current.
 */
final readonly class ChargingService
{
    public function __construct(
        private ChargingCommandClient $client,
        private VehicleRepositoryInterface $vehicleRepository,
    ) {}

    public function start(string $userId, Vin $vin): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->startCharging($vin);
    }

    public function stop(string $userId, Vin $vin): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->stopCharging($vin);
    }

    public function setChargeLimit(string $userId, Vin $vin, ChargeLimit $limit): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->setChargeLimit($vin, $limit);
    }

    public function setChargingAmps(string $userId, Vin $vin, ChargingAmps $amps): void
    {
        $this->assertOwnership($vin, $userId);
        $this->client->setChargingAmps($vin, $amps);
    }

    /** @throws VehicleUnauthorizedException */
    private function assertOwnership(Vin $vin, string $userId): void
    {
        if (!$this->vehicleRepository->isAccessibleBy($vin, $userId)) {
            throw new VehicleUnauthorizedException($vin->value, $userId);
        }
    }
}
