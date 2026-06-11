<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Climate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Climate\ValueObjects\CopTemp;
use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateControlClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(ClimateService::class)]
final class ClimateServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';
    private const USER = 'user-1';

    #[Test]
    public function activateSendsTheCommandWithTheRequestedTemperature(): void
    {
        $vin = new Vin(self::VIN);
        $temp = new Temperature(21.5);

        $client = $this->createMock(ClimateControlClient::class);
        $client->expects($this->once())->method('startClimate')->with($vin, $temp);

        $service = new ClimateService(
            $client,
            $this->vehicles(owned: true, vin: $vin),
            $this->waker(),
        );

        $service->activate(self::USER, $vin, $temp);
    }

    #[Test]
    public function activateThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $client = $this->createMock(ClimateControlClient::class);
        $client->expects($this->never())->method('startClimate');

        $service = new ClimateService($client, $this->vehicles(owned: false), $this->waker());

        $this->expectException(VehicleUnauthorizedException::class);
        $service->activate(self::USER, new Vin(self::VIN));
    }

    #[Test]
    public function deactivateSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $client = $this->createMock(ClimateControlClient::class);
        $client->expects($this->once())->method('stopClimate')->with($vin);

        $service = new ClimateService(
            $client,
            $this->vehicles(owned: true, vin: $vin),
            $this->waker(),
        );

        $service->deactivate(self::USER, $vin);
    }

    #[Test]
    public function applyKeeperModeSendsTheSelectedMode(): void
    {
        $vin = new Vin(self::VIN);

        $client = $this->createMock(ClimateControlClient::class);
        $client->expects($this->once())->method('setKeeperMode')->with($vin, KeeperMode::Dog);

        $service = new ClimateService(
            $client,
            $this->vehicles(owned: true, vin: $vin),
            $this->waker(),
        );

        $service->applyKeeperMode(self::USER, $vin, KeeperMode::Dog);
    }

    #[Test]
    public function applyCopTempSendsTheSelectedLevel(): void
    {
        $vin = new Vin(self::VIN);

        $client = $this->createMock(ClimateControlClient::class);
        $client->expects($this->once())->method('setCopTemp')->with($vin, CopTemp::Medium);

        $service = new ClimateService(
            $client,
            $this->vehicles(owned: true, vin: $vin),
            $this->waker(),
        );

        $service->applyCopTemp(self::USER, $vin, CopTemp::Medium);
    }

    #[Test]
    public function activateWakesTheVehicleAndRetriesWhenAsleep(): void
    {
        $vin = new Vin(self::VIN);

        $calls = 0;
        $client = $this->createMock(ClimateControlClient::class);
        $client
            ->expects($this->exactly(2))
            ->method('startClimate')
            ->with($vin)
            ->willReturnCallback(function () use (&$calls): void {
                if (++$calls === 1) {
                    throw new VehicleAsleepException('asleep');
                }
            });

        $wakeCommands = $this->createMock(VehicleCommandClient::class);
        $wakeCommands->expects($this->once())->method('wakeUp')->with($vin);

        $service = new ClimateService(
            $client,
            $this->vehicles(owned: true, vin: $vin),
            new VehicleWaker($wakeCommands, $this->createStub(VehicleStateClient::class), [0]),
        );

        $service->activate(self::USER, $vin);
    }

    /** Wake-transparent waker for the tests that do not exercise the asleep path. */
    private function waker(): VehicleWaker
    {
        return new VehicleWaker(
            $this->createStub(VehicleCommandClient::class),
            $this->createStub(VehicleStateClient::class),
            [0],
        );
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
