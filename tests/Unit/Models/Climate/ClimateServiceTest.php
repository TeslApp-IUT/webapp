<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Climate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Climate\ClimateService;
use Teslapp\Models\Climate\PreconditioningPlanner;
use Teslapp\Models\Climate\PreconditioningPlannerRepositoryInterface;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateCommandClient;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(ClimateService::class)]
final class ClimateServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';
    private const OTHER_VIN = '5YJ3E1EA7KF000999';
    private const USER = 'user-1';

    #[Test]
    public function listPlansThrowsWhenUserDoesNotOwnTheVehicle(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->expects($this->never())->method('findByVin');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new ClimateService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->listPlansForVehicle(self::USER, new Vin(self::VIN));
    }

    #[Test]
    public function listPlansReturnsTheVehicleSchedules(): void
    {
        $vin = new Vin(self::VIN);
        $plan = $this->makePlanner($vin);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners
            ->method('findByVin')
            ->with($vin)
            ->willReturn([$plan]);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->with($vin, self::USER)->willReturn(true);

        $service = new ClimateService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        self::assertSame([$plan], $service->listPlansForVehicle(self::USER, $vin));
    }

    #[Test]
    public function createPlanWithLocationPushesToTeslaAndStoresTheScheduleId(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn('plan-1');
        $planners->expects($this->once())->method('setTeslaScheduleId')->with('plan-1', 999);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(999);

        $service = new ClimateService($planners, $vehicles, $climate);

        $id = $service->createPlan(
            self::USER,
            new Vin(self::VIN),
            '07:30',
            [DayOfWeek::Monday],
            memorizeLongTerm: true,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
        );

        self::assertSame('plan-1', $id);
    }

    #[Test]
    public function createPlanWithoutLocationDoesNotPushToTesla(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn('plan-1');
        $planners->expects($this->never())->method('setTeslaScheduleId');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->never())->method('addPreconditionSchedule');

        $service = new ClimateService($planners, $vehicles, $climate);

        $service->createPlan(
            self::USER,
            new Vin(self::VIN),
            '07:30',
            [DayOfWeek::Monday],
            true,
            true,
        );
    }

    #[Test]
    public function createPlanDoesNotStoreScheduleIdWhenTeslaReturnsNone(): void
    {
        // Dry-run path: the push returns no id, so nothing is stored.
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn('plan-1');
        $planners->expects($this->never())->method('setTeslaScheduleId');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(null);

        $service = new ClimateService($planners, $vehicles, $climate);

        $service->createPlan(
            self::USER,
            new Vin(self::VIN),
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

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: 555));
        $planners->expects($this->once())->method('update');
        $planners->expects($this->once())->method('setTeslaScheduleId')->with('plan-1', 555);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(555);

        $service = new ClimateService($planners, $vehicles, $climate);

        $service->updatePlan(
            self::USER,
            $vin,
            'plan-1',
            '08:00',
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

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin));
        $planners->expects($this->once())->method('setEnabled')->with('plan-1', false);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new ClimateService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $service->setPlanEnabled(self::USER, $vin, 'plan-1', false);
    }

    #[Test]
    public function setPlanEnabledThrowsWhenThePlannerBelongsToAnotherVehicle(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner(new Vin(self::OTHER_VIN)));
        $planners->expects($this->never())->method('setEnabled');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new ClimateService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->setPlanEnabled(self::USER, new Vin(self::VIN), 'plan-1', false);
    }

    #[Test]
    public function deletePlanRemovesTheTeslaScheduleThenDeletes(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: 777));
        $planners->expects($this->once())->method('deleteById')->with('plan-1');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('removePreconditionSchedule')->with($vin, 777);

        $service = new ClimateService($planners, $vehicles, $climate);

        $service->deletePlan(self::USER, $vin, 'plan-1');
    }

    #[Test]
    public function deletePlanSkipsTeslaRemovalWhenNoScheduleId(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: null));
        $planners->expects($this->once())->method('deleteById')->with('plan-1');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->never())->method('removePreconditionSchedule');

        $service = new ClimateService($planners, $vehicles, $climate);

        $service->deletePlan(self::USER, $vin, 'plan-1');
    }

    private function makePlanner(Vin $vin, ?int $teslaScheduleId = null): PreconditioningPlanner
    {
        return new PreconditioningPlanner(
            id: 'plan-1',
            vin: $vin,
            activationHour: '07:30',
            deactivateAfterSuccess: false,
            days: [DayOfWeek::Monday],
            enabled: true,
            teslaScheduleId: $teslaScheduleId,
        );
    }
}
