<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ChargingPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\Vin;

#[CoversClass(ChargingPlanner::class)]
final class ChargingPlannerTest extends TestCase
{
    #[Test]
    public function convertsTheWindowToMinutesAfterMidnight(): void
    {
        $planner = $this->planner('07:30', '08:45');

        self::assertSame(7 * 60 + 30, $planner->startTimeMinutes());
        self::assertSame(8 * 60 + 45, $planner->endTimeMinutes());
    }

    #[Test]
    public function allowsAWindowCrossingMidnight(): void
    {
        // Off-peak tariff case: the window opens at night and closes the next morning.
        $planner = $this->planner('23:30', '07:30');

        self::assertSame(23 * 60 + 30, $planner->startTimeMinutes());
        self::assertSame(7 * 60 + 30, $planner->endTimeMinutes());
    }

    #[Test]
    public function endTimeIsNullWhenTheWindowIsOpenEnded(): void
    {
        self::assertNull($this->planner('07:30')->endTimeMinutes());
    }

    #[Test]
    public function buildsTheTeslaDaysOfWeekCsv(): void
    {
        $planner = $this->planner('07:30', days: [DayOfWeek::Monday, DayOfWeek::Thursday]);

        self::assertSame('Monday,Thursday', $planner->daysOfWeekCsv());
    }

    #[Test]
    public function returnsSortedDayIds(): void
    {
        $planner = $this->planner('07:30', days: [DayOfWeek::Thursday, DayOfWeek::Monday]);

        self::assertSame([1, 4], $planner->dayIds());
    }

    #[Test]
    public function isMemorizedLongTermWhenItDoesNotDeactivateAfterSuccess(): void
    {
        self::assertTrue($this->planner('07:30', deactivate: false)->isMemorizedLongTerm());
        self::assertFalse($this->planner('07:30', deactivate: true)->isMemorizedLongTerm());
    }

    #[Test]
    public function fromRowReadsTheWindowAndLocation(): void
    {
        $planner = ChargingPlanner::fromRow(
            [
                'id' => 'planner-1',
                'vin' => '5YJ3E1EA7KF000316',
                'activation_hour' => '23:30:00',
                'deactivation_hour' => '07:30:00',
                'deactivate_after_success' => true,
                'enabled' => true,
                'activation_latitude' => '43.2965',
                'activation_longitude' => '5.3698',
                'location_label' => 'Marseille, France',
                'tesla_schedule_id' => '999',
            ],
            [1, 4],
        );

        self::assertSame('23:30', $planner->activationHour);
        self::assertSame('07:30', $planner->deactivationHour);
        self::assertTrue($planner->enabled);
        self::assertNotNull($planner->location);
        self::assertSame(43.2965, $planner->location->latitude);
        self::assertSame('Marseille, France', $planner->locationLabel);
        self::assertSame(999, $planner->teslaScheduleId);
        self::assertSame([DayOfWeek::Monday, DayOfWeek::Thursday], $planner->days);
    }

    #[Test]
    public function fromRowLeavesTheOptionalFieldsNull(): void
    {
        $planner = ChargingPlanner::fromRow(
            [
                'id' => 'planner-1',
                'vin' => '5YJ3E1EA7KF000316',
                'activation_hour' => '23:30:00',
                'deactivation_hour' => null,
                'deactivate_after_success' => false,
                'enabled' => false,
                'activation_latitude' => null,
                'activation_longitude' => null,
                'location_label' => null,
                'tesla_schedule_id' => null,
            ],
            [1],
        );

        self::assertNull($planner->deactivationHour);
        self::assertFalse($planner->enabled);
        self::assertNull($planner->location);
        self::assertNull($planner->locationLabel);
        self::assertNull($planner->teslaScheduleId);
    }

    /** @param list<DayOfWeek> $days */
    private function planner(
        string $start,
        ?string $end = null,
        array $days = [DayOfWeek::Monday],
        bool $deactivate = true,
    ): ChargingPlanner {
        return new ChargingPlanner(
            id: null,
            vin: new Vin('5YJ3E1EA7KF000316'),
            activationHour: $start,
            deactivationHour: $end,
            deactivateAfterSuccess: $deactivate,
            days: $days,
        );
    }
}
