<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Climate;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Climate\PreconditioningPlanner;
use Teslapp\Models\Shared\ValueObjects\DayOfWeek;
use Teslapp\Models\Shared\ValueObjects\Vin;

#[CoversClass(PreconditioningPlanner::class)]
final class PreconditioningPlannerTest extends TestCase
{
    #[Test]
    public function convertsActivationHourToMinutesAfterMidnight(): void
    {
        self::assertSame(7 * 60 + 30, $this->planner('07:30')->preconditionTimeMinutes());
    }

    #[Test]
    public function buildsTheTeslaDaysOfWeekCsv(): void
    {
        $planner = $this->planner('07:30', [DayOfWeek::Monday, DayOfWeek::Thursday]);

        self::assertSame('Monday,Thursday', $planner->daysOfWeekCsv());
    }

    #[Test]
    public function returnsSortedDayIds(): void
    {
        $planner = $this->planner('07:30', [DayOfWeek::Thursday, DayOfWeek::Monday]);

        self::assertSame([1, 4], $planner->dayIds());
    }

    #[Test]
    public function isMemorizedLongTermWhenItDoesNotDeactivateAfterSuccess(): void
    {
        self::assertTrue($this->planner('07:30', deactivate: false)->isMemorizedLongTerm());
        self::assertFalse($this->planner('07:30', deactivate: true)->isMemorizedLongTerm());
    }

    #[Test]
    public function fromRowReadsTheLocationWhenPresent(): void
    {
        $planner = PreconditioningPlanner::fromRow([
            'id' => 'planner-1',
            'vin' => '5YJ3E1EA7KF000316',
            'activation_hour' => '07:30:00',
            'deactivate_after_success' => true,
            'enabled' => true,
            'activation_latitude' => '43.2965',
            'activation_longitude' => '5.3698',
            'location_label' => 'Marseille, France',
        ], [1, 4]);

        self::assertSame('07:30', $planner->activationHour);
        self::assertTrue($planner->enabled);
        self::assertNotNull($planner->location);
        self::assertSame(43.2965, $planner->location->latitude);
        self::assertSame('Marseille, France', $planner->locationLabel);
        self::assertSame([DayOfWeek::Monday, DayOfWeek::Thursday], $planner->days);
    }

    #[Test]
    public function fromRowLeavesTheLocationNullWhenAbsent(): void
    {
        $planner = PreconditioningPlanner::fromRow([
            'id' => 'planner-1',
            'vin' => '5YJ3E1EA7KF000316',
            'activation_hour' => '07:30:00',
            'deactivate_after_success' => false,
            'enabled' => false,
            'activation_latitude' => null,
            'activation_longitude' => null,
            'location_label' => null,
        ], [1]);

        self::assertFalse($planner->enabled);
        self::assertNull($planner->location);
        self::assertNull($planner->locationLabel);
    }

    /** @param list<DayOfWeek> $days */
    private function planner(
        string $hour,
        array $days = [DayOfWeek::Monday],
        bool $deactivate = true,
    ): PreconditioningPlanner {
        return new PreconditioningPlanner(
            id: null,
            vin: new Vin('5YJ3E1EA7KF000316'),
            activationHour: $hour,
            deactivateAfterSuccess: $deactivate,
            days: $days,
        );
    }
}
