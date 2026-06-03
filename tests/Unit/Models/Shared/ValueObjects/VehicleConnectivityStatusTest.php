<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\ValueObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;

#[CoversClass(VehicleConnectivityStatus::class)]
final class VehicleConnectivityStatusTest extends TestCase
{
    #[Test]
    public function mapsKnownApiStates(): void
    {
        self::assertSame(
            VehicleConnectivityStatus::Online,
            VehicleConnectivityStatus::fromApiState('online'),
        );
        self::assertSame(
            VehicleConnectivityStatus::Asleep,
            VehicleConnectivityStatus::fromApiState('asleep'),
        );
        self::assertSame(
            VehicleConnectivityStatus::Offline,
            VehicleConnectivityStatus::fromApiState('offline'),
        );
    }

    #[Test]
    public function isCaseInsensitive(): void
    {
        self::assertSame(
            VehicleConnectivityStatus::Online,
            VehicleConnectivityStatus::fromApiState('ONLINE'),
        );
    }

    #[Test]
    public function fallsBackToUnknownForUnexpectedStates(): void
    {
        self::assertSame(
            VehicleConnectivityStatus::Unknown,
            VehicleConnectivityStatus::fromApiState('charging'),
        );
        self::assertSame(
            VehicleConnectivityStatus::Unknown,
            VehicleConnectivityStatus::fromApiState(''),
        );
    }

    #[Test]
    public function everyCaseHasANonEmptyLabel(): void
    {
        foreach (VehicleConnectivityStatus::cases() as $status) {
            self::assertNotSame('', $status->label());
        }
    }
}
