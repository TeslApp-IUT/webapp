<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Climate;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Climate\PreconditioningService;
use Teslapp\Models\Climate\PreconditioningPlanner;
use Teslapp\Models\Climate\PreconditioningPlannerRepositoryInterface;
use Teslapp\Models\Shared\Exceptions\VehicleUnauthorizedException;
use Teslapp\Models\Shared\TeslaApi\ClimateCommandClient;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\GeoPoint;
use Teslapp\Models\Shared\ValueObjects\Vin;
use Teslapp\Models\Vehicle\VehicleRepositoryInterface;

#[CoversClass(PreconditioningService::class)]
final class PreconditioningServiceTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';
    private const OTHER_VIN = '5YJ3E1EA7KF000999';
    private const USER = 'user-1';
    private const PLAN_ID = '11111111-1111-4111-8111-111111111111';

    #[Test]
    public function listPlansThrowsWhenUserDoesNotOwnTheVehicle(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->expects($this->never())->method('findByVin');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(false);

        $service = new PreconditioningService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->listPlansForVehicle(self::USER, new Vin(self::VIN));
    }

    #[Test]
    public function deletePlanRejectsAMalformedPlanId(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->expects($this->never())->method('findById');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new PreconditioningService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $this->expectException(InvalidArgumentException::class);
        $service->deletePlan(self::USER, new Vin(self::VIN), 'not-a-uuid');
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

        $service = new PreconditioningService(
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
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->once())->method('setTeslaScheduleId')->with(self::PLAN_ID, 999);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(999);

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $id = $service->createPlan(
            self::USER,
            new Vin(self::VIN),
            '07:30',
            [DayOfWeek::Monday],
            memorizeLongTerm: true,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
        );

        self::assertSame(self::PLAN_ID, $id);
    }

    #[Test]
    public function setPlanEnabledPushesTheNewStateToTesla(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners
            ->method('findById')
            ->willReturn(
                $this->makePlanner($vin, teslaScheduleId: 555, location: new GeoPoint(43.5, 5.4)),
            );
        $planners->expects($this->once())->method('setEnabled')->with(self::PLAN_ID, false);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate
            ->expects($this->once())
            ->method('addPreconditionSchedule')
            ->willReturnCallback(static function (
                Vin $v,
                int $minutes,
                string $days,
                bool $enabled,
            ): ?int {
                self::assertFalse($enabled);

                return 555;
            });

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $service->setPlanEnabled(self::USER, $vin, self::PLAN_ID, false);
    }

    #[Test]
    public function createPlanDoesNotStoreScheduleIdWhenTeslaReturnsNone(): void
    {
        // Dry-run path: the push returns no id, so nothing is stored.
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('save')->willReturn(self::PLAN_ID);
        $planners->expects($this->never())->method('setTeslaScheduleId');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(null);

        $service = new PreconditioningService($planners, $vehicles, $climate);

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
        $planners->expects($this->once())->method('setTeslaScheduleId')->with(self::PLAN_ID, 555);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('addPreconditionSchedule')->willReturn(555);

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $service->updatePlan(
            self::USER,
            $vin,
            self::PLAN_ID,
            '08:00',
            [DayOfWeek::Tuesday],
            memorizeLongTerm: false,
            enabled: true,
            location: new GeoPoint(43.5, 5.4),
        );
    }

    #[Test]
    public function setPlanEnabledWithoutLocationDoesNotPushToTesla(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin));
        $planners->expects($this->once())->method('setEnabled')->with(self::PLAN_ID, false);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->never())->method('addPreconditionSchedule');

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $service->setPlanEnabled(self::USER, $vin, self::PLAN_ID, false);
    }

    #[Test]
    public function setPlanEnabledThrowsWhenThePlannerBelongsToAnotherVehicle(): void
    {
        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner(new Vin(self::OTHER_VIN)));
        $planners->expects($this->never())->method('setEnabled');

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $service = new PreconditioningService(
            $planners,
            $vehicles,
            $this->createMock(ClimateCommandClient::class),
        );

        $this->expectException(VehicleUnauthorizedException::class);
        $service->setPlanEnabled(self::USER, new Vin(self::VIN), self::PLAN_ID, false);
    }

    #[Test]
    public function deletePlanRemovesTheTeslaScheduleThenDeletes(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: 777));
        $planners->expects($this->once())->method('deleteById')->with(self::PLAN_ID);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->once())->method('removePreconditionSchedule')->with($vin, 777);

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $service->deletePlan(self::USER, $vin, self::PLAN_ID);
    }

    #[Test]
    public function deletePlanSkipsTeslaRemovalWhenNoScheduleId(): void
    {
        $vin = new Vin(self::VIN);

        $planners = $this->createMock(PreconditioningPlannerRepositoryInterface::class);
        $planners->method('findById')->willReturn($this->makePlanner($vin, teslaScheduleId: null));
        $planners->expects($this->once())->method('deleteById')->with(self::PLAN_ID);

        $vehicles = $this->createMock(VehicleRepositoryInterface::class);
        $vehicles->method('isAccessibleBy')->willReturn(true);

        $climate = $this->createMock(ClimateCommandClient::class);
        $climate->expects($this->never())->method('removePreconditionSchedule');

        $service = new PreconditioningService($planners, $vehicles, $climate);

        $service->deletePlan(self::USER, $vin, self::PLAN_ID);
    }

    private function makePlanner(
        Vin $vin,
        ?int $teslaScheduleId = null,
        ?GeoPoint $location = null,
    ): PreconditioningPlanner {
        return new PreconditioningPlanner(
            id: self::PLAN_ID,
            vin: $vin,
            activationHour: '07:30',
            deactivateAfterSuccess: false,
            days: [DayOfWeek::Monday],
            enabled: true,
            location: $location,
            teslaScheduleId: $teslaScheduleId,
        );
    }
}
