<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\TeslaApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaChargingClient;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Covers the TESLA_COMMANDS_DRY_RUN safety guard of TeslaChargingClient.
 *
 * The adapter itself is not otherwise unit-tested (the ChargingCommandClient port
 * is mocked elsewhere); only the guard — which decides whether a real command is
 * sent — is exercised here.
 */
#[CoversClass(TeslaChargingClient::class)]
final class TeslaChargingClientTest extends TestCase
{
    private const VIN = '5YJ3E1EA7KF000316';

    protected function setUp(): void
    {
        // No authenticated session: TeslaHttpClient would have no user token.
        $_SESSION = [];
    }

    #[Test]
    public function dryRunDoesNotSendTheCommand(): void
    {
        $client = new TeslaChargingClient(dryRun: true);

        // Guard active: post() returns before any HTTP call, so no token is needed
        // and no exception is thrown (the command is simulated, not sent).
        $client->startCharging(new Vin(self::VIN));

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function dryRunReturnsNoScheduleId(): void
    {
        $client = new TeslaChargingClient(dryRun: true);

        $id = $client->addChargeSchedule(
            new Vin(self::VIN),
            startTimeMinutes: 1410,
            endTimeMinutes: 450,
            daysOfWeekCsv: 'Monday',
            enabled: true,
            oneTime: false,
            lat: 43.5,
            lon: 5.4,
        );

        self::assertNull($id);
    }

    #[Test]
    public function withoutDryRunItReachesTheHttpCall(): void
    {
        $client = new TeslaChargingClient(dryRun: false);

        // Guard off: post() calls TeslaHttpClient::post, which needs a user token
        // from the session. There is none, so it throws before any network call —
        // proving the real-command path is taken when the guard is disabled.
        $this->expectException(TeslaApiException::class);

        $client->setChargeLimit(new Vin(self::VIN), new ChargeLimit(80));
    }
}
