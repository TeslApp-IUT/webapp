<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Vehicle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\TrunkSide;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleCommandService;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(VehicleCommandService::class)]
final class VehicleCommandServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';

    #[Test]
    public function lockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('lock')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->lock('user-1', $vin, $token);
    }

    #[Test]
    public function lockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('lock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->lock('user-2', $vin, $token);
    }

    #[Test]
    public function unlockSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('unlock')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->unlock('user-1', $vin, $token);
    }

    #[Test]
    public function unlockThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('unlock');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->unlock('user-2', $vin, $token);
    }

    #[Test]
    public function honkHornSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('honkHorn')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->honkHorn('user-1', $vin, $token);
    }

    #[Test]
    public function honkHornThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('honkHorn');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->honkHorn('user-2', $vin, $token);
    }

    #[Test]
    public function flashLightsSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('flashLights')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->flashLights('user-1', $vin, $token);
    }

    #[Test]
    public function flashLightsThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('flashLights');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->flashLights('user-2', $vin, $token);
    }

    #[Test]
    public function actuateTrunkSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('actuateTrunk')->with($token, $vin, TrunkSide::Rear);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->actuateTrunk('user-1', $vin, TrunkSide::Rear, $token);
    }

    #[Test]
    public function actuateTrunkThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('actuateTrunk');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->actuateTrunk('user-2', $vin, TrunkSide::Front, $token);
    }

    #[Test]
    public function openChargePortDoorSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('openChargePortDoor')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->openChargePortDoor('user-1', $vin, $token);
    }

    #[Test]
    public function openChargePortDoorThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('openChargePortDoor');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->openChargePortDoor('user-2', $vin, $token);
    }

    #[Test]
    public function closeChargePortDoorSendsTheCommandWhenTheUserOwnsTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->once())->method('closeChargePortDoor')->with($token, $vin);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, 'user-1')->willReturn(true);

        (new VehicleCommandService($commands, $vehicles))->closeChargePortDoor('user-1', $vin, $token);
    }

    #[Test]
    public function closeChargePortDoorThrowsWhenTheUserDoesNotOwnTheVehicle(): void
    {
        $vin = new Vin(self::VIN);
        $token = new AccessToken('tok');

        $commands = $this->createMock(VehicleCommandClient::class);
        $commands->expects($this->never())->method('closeChargePortDoor');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new VehicleCommandService($commands, $vehicles);

        $this->expectException(VehicleUnauthorizedException::class);
        $service->closeChargePortDoor('user-2', $vin, $token);
    }
}
