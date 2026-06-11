<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(VehicleCommandService::class)]
final class VehicleCommandServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';

    /** @param list<int> $wakeRetryDelays Zero-second delays so tests never really sleep. */
    private function service(
        VehicleCommandClient $commands,
        VehicleRepositoryInterface $vehicles,
        array $wakeRetryDelays = [0],
    ): VehicleCommandService {
        return new VehicleCommandService(
            $commands,
            $vehicles,
            new VehicleWaker($commands, $wakeRetryDelays),
        );
    }

    #[Test]
    public function lockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('lock')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->lock('user-1', $vin);
    }

    #[Test]
    public function lockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('lock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->lock('user-2', $vin);
    }

    #[Test]
    public function unlockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('unlock')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->unlock('user-1', $vin);
    }

    #[Test]
    public function unlockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('unlock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->unlock('user-2', $vin);
    }

    #[Test]
    public function honkHornSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('honkHorn')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->honkHorn('user-1', $vin);
    }

    #[Test]
    public function honkHornThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('honkHorn');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->honkHorn('user-2', $vin);
    }

    #[Test]
    public function flashLightsSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('flashLights')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->flashLights('user-1', $vin);
    }

    #[Test]
    public function flashLightsThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('flashLights');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->flashLights('user-2', $vin);
    }

    #[Test]
    public function actuateTrunkSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('actuateTrunk')->with($vin, TrunkSide::Rear);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->actuateTrunk('user-1', $vin, TrunkSide::Rear);
    }

    #[Test]
    public function actuateTrunkThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('actuateTrunk');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->actuateTrunk('user-2', $vin, TrunkSide::Front);
    }

    #[Test]
    public function openChargePortDoorSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('openChargePortDoor')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->openChargePortDoor('user-1', $vin);
    }

    #[Test]
    public function openChargePortDoorThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('openChargePortDoor');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->openChargePortDoor('user-2', $vin);
    }

    #[Test]
    public function closeChargePortDoorSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('closeChargePortDoor')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->closeChargePortDoor('user-1', $vin);
    }

    #[Test]
    public function closeChargePortDoorThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('closeChargePortDoor');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->closeChargePortDoor('user-2', $vin);
    }

    #[Test]
    public function wakeUpSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        $this->service($commands, $vehicles)->wakeUp('user-1', $vin);
    }

    #[Test]
    public function wakeUpThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('wakeUp');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = $this->service($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->wakeUp('user-2', $vin);
    }

    #[Test]
    public function lockWakesTheVehicleAndRetriesWhenAsleep(): void
    {
        $vin = new Vin(self::VIN);

        $calls = 0;
        $commands = $this->createMock(VehicleCommandClient::class);
        $commands
            ->expects($this->exactly(2))
            ->method('lock')
            ->with($vin)
            ->willReturnCallback(function () use (&$calls): void {
                if (++$calls === 1) {
                    throw new VehicleAsleepException('asleep');
                }
            });
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $this->service($commands, $vehicles)->lock('user-1', $vin);
    }

    #[Test]
    public function lockGivesUpWhenTheVehicleStaysAsleep(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        // 1 initial attempt + 2 retries (one per delay), all rejected as asleep.
        $commands
            ->expects($this->exactly(3))
            ->method('lock')
            ->willThrowException(new VehicleAsleepException('asleep'));
        $commands->expects($this->once())->method('wakeUp')->with($vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = $this->service($commands, $vehicles, [0, 0]);

        $this->expectException(VehicleAsleepException::class);
        $service->lock('user-1', $vin);
    }

    #[Test]
    public function lockDoesNotWakeOnOtherApiErrors(): void
    {
        $vin = new Vin(self::VIN);

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands
            ->expects($this->once())
            ->method('lock')
            ->willThrowException(new TeslaApiException('command failed'));
        $commands->expects($this->never())->method('wakeUp');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = $this->service($commands, $vehicles);

        $this->expectException(TeslaApiException::class);
        $service->lock('user-1', $vin);
    }
}
