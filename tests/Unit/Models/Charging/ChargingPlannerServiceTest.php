<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ChargingPlanner;
use Teslapp\Models\Charging\ChargingPlannerRepositoryInterface;
use Teslapp\Models\Charging\ChargingPlannerService;
use Teslapp\Models\Shared\Exceptions\VehicleAsleepException;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ChargingCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleCommandClient;
use Teslapp\Models\Shared\TeslaApi\VehicleStateClient;
use Teslapp\Models\Shared\TeslaApi\VehicleWaker;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(ChargingPlannerService::class)]
final class ChargingPlannerServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';
    private const OTHER_VIN = '5YJ3E1EA7KF000999';
    private const USER = 'user-1';
    private const PLAN_ID = '11111111-1111-4111-8111-111111111111';

    #[Test]
    public function listPlansThrowsWhenUserDoesNotOwnTheVehicle(): void
    {
        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->expects($this->never())->method('findByVin');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $this->createMock(ChargingCommandClient::class),
            $this->waker(),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->listPlansForVehicle(self::USER, new Vin(self::VIN));
    }

    #[Test]
    public function deletePlanRejectsAMalformedPlanId(): void
    {
        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->expects($this->never())->method('findById');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $this->createMock(ChargingCommandClient::class),
            $this->waker(),
        );

        $this->expectException(InvalidArgumentException::class);
        $service->deletePlan(self::USER, new Vin(self::VIN), 'not-a-uuid');
    }

    #[Test]
    public function listPlansReturnsTheVehicleSchedules(): void
    {
        $vin = new Vin(self::VIN);
        $plan = $this->makePlanner($vin);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners
            ->method('findByVin')
            ->with($vin)
            ->willReturn([$plan]);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, self::USER)->willReturn(true);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $this->createMock(ChargingCommandClient::class),
            $this->waker(),
        );

        self::assertSame([$plan], $service->listPlansForVehicle(self::USER, $vin));
    }

    #[Test]
    public function createPlanWithLocationPushesTheWindowAndStoresTheScheduleId(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->once())->method('setTeslaScheduleId')->with(self::PLAN_ID, 999);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging
            ->expects($this->once())
            ->method('addChargeSchedule')
            // Off-peak window 23:30 → 07:30 (crossing midnight), recurring, named.
            ->with($vin, 1410, 450, 'Monday', true, false, 43.5, 5.4, 'Maison', null)
            ->willReturn(999);

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $id = $service->createPlan(
            self::USER,
            $vin,
            '23:30',
            '07:30',
            [DayOfWeek::Monday],
            memorizeLongTerm: true,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
            locationLabel: 'Maison',
        );

        self::assertSame(self::PLAN_ID, $id);
    }

    #[Test]
    public function createPlanWithoutLocationDoesNotPushToTesla(): void
    {
        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->never())->method('setTeslaScheduleId');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging->expects($this->never())->method('addChargeSchedule');

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $service->createPlan(
            self::USER,
            new Vin(self::VIN),
            '23:30',
            null,
            [DayOfWeek::Monday],
            true,
            true,
        );
    }

    #[Test]
    public function createPlanDoesNotStoreScheduleIdWhenTeslaReturnsNone(): void
    {
        // Dry-run path: the push returns no id, so nothing is stored.
        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->never())->method('setTeslaScheduleId');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging->expects($this->once())->method('addChargeSchedule')->willReturn(null);

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $service->createPlan(
            self::USER,
            new Vin(self::VIN),
            '23:30',
            '07:30',
            [DayOfWeek::Monday],
            true,
            true,
            new GeoPoint(43.5, 5.4),
        );
    }

    #[Test]
    public function updatePlanPersistsAndPushesWithLocation(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: 555));
        $planners->expects($this->once())->method('update');
        $planners->expects($this->once())->method('setTeslaScheduleId')->with(self::PLAN_ID, 555);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging->expects($this->once())->method('addChargeSchedule')->willReturn(555);

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $service->updatePlan(
            self::USER,
            $vin,
            self::PLAN_ID,
            '22:00',
            '06:00',
            [DayOfWeek::Tuesday],
            memorizeLongTerm: false,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
        );
    }

    #[Test]
    public function setPlanEnabledTogglesThroughTheRepository(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin));
        $planners->expects($this->once())->method('setEnabled')->with(self::PLAN_ID, false);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $this->createMock(ChargingCommandClient::class),
            $this->waker(),
        );

        $service->setPlanEnabled(self::USER, $vin, self::PLAN_ID, false);
    }

    #[Test]
    public function setPlanEnabledThrowsWhenThePlannerBelongsToAnotherVehicle(): void
    {
        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner(new Vin(self::OTHER_VIN)));
        $planners->expects($this->never())->method('setEnabled');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $this->createMock(ChargingCommandClient::class),
            $this->waker(),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->setPlanEnabled(self::USER, new Vin(self::VIN), self::PLAN_ID, false);
    }

    #[Test]
    public function deletePlanRemovesTheTeslaScheduleThenDeletes(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: 777));
        $planners->expects($this->once())->method('deleteById')->with(self::PLAN_ID);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging->expects($this->once())->method('removeChargeSchedule')->with($vin, 777);

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $service->deletePlan(self::USER, $vin, self::PLAN_ID);
    }

    #[Test]
    public function deletePlanSkipsTeslaRemovalWhenNoScheduleId(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: null));
        $planners->expects($this->once())->method('deleteById')->with(self::PLAN_ID);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $charging = $this->createMock(ChargingCommandClient::class);
        $charging->expects($this->never())->method('removeChargeSchedule');

        $service = new ChargingPlannerService($planners, $vehicles, $charging, $this->waker());

        $service->deletePlan(self::USER, $vin, self::PLAN_ID);
    }

    #[Test]
    public function createPlanStoresTheScheduleIdWhenTheVehicleWakesUp(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(ChargingPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->once())->method('setTeslaScheduleId')->with(self::PLAN_ID, 999);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $calls = 0;
        $charging = $this->createMock(ChargingCommandClient::class);
        $charging
            ->expects($this->exactly(2))
            ->method('addChargeSchedule')
            ->willReturnCallback(function () use (&$calls): ?int {
                if (++$calls === 1) {
                    throw new VehicleAsleepException('asleep');
                }

                return 999;
            });

        $wakeCommands = $this->createMock(VehicleCommandClient::class);
        $wakeCommands->expects($this->once())->method('wakeUp')->with($vin);

        $service = new ChargingPlannerService(
            $planners,
            $vehicles,
            $charging,
            new VehicleWaker($wakeCommands, $this->createStub(VehicleStateClient::class), [0]),
        );

        $service->createPlan(
            self::USER,
            $vin,
            '23:30',
            '07:30',
            [DayOfWeek::Monday],
            memorizeLongTerm: true,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
            locationLabel: 'Maison',
        );
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

    private function makePlanner(Vin $vin, ?int $teslaScheduleId = null): ChargingPlanner
    {
        return new ChargingPlanner(
            id: self::PLAN_ID,
            vin: $vin,
            activationHour: '23:30',
            deactivationHour: '07:30',
            deactivateAfterSuccess: false,
            days: [DayOfWeek::Monday],
            enabled: true,
            teslaScheduleId: $teslaScheduleId,
        );
    }
}
