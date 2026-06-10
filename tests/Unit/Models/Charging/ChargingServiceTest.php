<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ChargingService;
use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ChargingCommandClient;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(ChargingService::class)]
final class ChargingServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';
    private const USER = 'user-1';

    #[Test]
    public function startSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->once())->method('startCharging')->with($vin);

        $service = new ChargingService($client, $this->vehicles(owned: true, vin: $vin));

        $service->start(self::USER, $vin);
    }

    #[Test]
    public function startThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->never())->method('startCharging');

        $service = new ChargingService($client, $this->vehicles(owned: false));

        $this->expectException(VehicleUnauthorizedException::class);
        $service->start(self::USER, new Vin(self::VIN));
    }

    #[Test]
    public function stopSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->once())->method('stopCharging')->with($vin);

        $service = new ChargingService($client, $this->vehicles(owned: true, vin: $vin));

        $service->stop(self::USER, $vin);
    }

    #[Test]
    public function setChargeLimitPassesTheValidatedLimit(): void
    {
        $vin = new Vin(self::VIN);
        $limit = new ChargeLimit(80);

        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->once())->method('setChargeLimit')->with($vin, $limit);

        $service = new ChargingService($client, $this->vehicles(owned: true, vin: $vin));

        $service->setChargeLimit(self::USER, $vin, $limit);
    }

    #[Test]
    public function setChargeLimitThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->never())->method('setChargeLimit');

        $service = new ChargingService($client, $this->vehicles(owned: false));

        $this->expectException(VehicleUnauthorizedException::class);
        $service->setChargeLimit(self::USER, new Vin(self::VIN), new ChargeLimit(80));
    }

    #[Test]
    public function setChargingAmpsPassesTheValidatedAmps(): void
    {
        $vin = new Vin(self::VIN);
        $amps = new ChargingAmps(16);

        $client = $this->createMock(ChargingCommandClient::class);
        $client->expects($this->once())->method('setChargingAmps')->with($vin, $amps);

        $service = new ChargingService($client, $this->vehicles(owned: true, vin: $vin));

        $service->setChargingAmps(self::USER, $vin, $amps);
    }

    private function vehicles(bool $owned, ?Vin $vin = null): VehicleRepositoryInterface
    {
        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $expectation = $vehicles->method('isAccessibleBy');
        if ($vin !== null) {
            $expectation->with($vin, self::USER);
        }
        $expectation->willReturn($owned);

        return $vehicles;
    }
}
