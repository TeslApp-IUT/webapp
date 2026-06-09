<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\TeslaApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaCommandClient;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Covers the TESLA_COMMANDS_DRY_RUN safety guard of TeslaCommandClient.
 *
 * The adapter itself is not otherwise unit-tested (the VehicleCommandClient port
 * is mocked elsewhere); only the guard — which decides whether a real command is
 * sent — is exercised here.
 */
#[CoversClass(TeslaCommandClient::class)]
final class TeslaCommandClientTest extends TestCase
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
        $client = new TeslaCommandClient(dryRun: true);

        // Guard active: send() returns before any HTTP call, so no token is needed
        // and no exception is thrown (the command is simulated, not sent).
        $client->lock(new Vin(self::VIN));

        $this->expectNotToPerformAssertions();
    }

    #[Test]
    public function withoutDryRunItReachesTheHttpCall(): void
    {
        $client = new TeslaCommandClient(dryRun: false);

        // Guard off: send() calls TeslaHttpClient::post, which needs a user token
        // from the session. There is none, so it throws before any network call —
        // proving the real-command path is taken when the guard is disabled.
        $this->expectException(TeslaApiException::class);

        $client->lock(new Vin(self::VIN));
    }
}
