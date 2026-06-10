<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Charging\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ValueObjects\ChargingAction;

#[CoversClass(ChargingAction::class)]
final class ChargingActionTest extends TestCase
{
    #[Test]
    public function mapsThePostedFormValues(): void
    {
        self::assertSame(ChargingAction::Start, ChargingAction::tryFrom('start'));
        self::assertSame(ChargingAction::Stop, ChargingAction::tryFrom('stop'));
    }

    #[Test]
    public function rejectsAnUnknownValue(): void
    {
        self::assertNull(ChargingAction::tryFrom('pause'));
    }
}
