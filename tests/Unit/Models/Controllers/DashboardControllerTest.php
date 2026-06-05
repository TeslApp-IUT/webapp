<?php
declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Controllers;

use DateTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Validates the shape (keys and value types) of the telemetry payload
 * the dashboard renders.
 **/
final class DashboardControllerTest extends TestCase
{
    private static function validPayload(): array
    {
        return [
            'battery_level' => 72,
            'charge_enable_request' => true,
            'scheduled_charging_start_time' => '2026-05-29 10:47:00',
            'inside_temp' => 21.5,
            'climate_keeper_mode' => 1,
            'hvac_ac_enabled' => true,
        ];
    }

    private static function malformedPayload(): array
    {
        return [
            'battery_level' => null,
            'charge_enable_request' => false,
            'scheduled_charging_start_time' => 'b',
            'inside_temp' => 56,
            'climate_keeper_mode' => 0,
            'hvac_ac_enabled' => '',
        ];
    }

    /* valid */
    #[Test]
    public function validPayloadHasAllExpectedKeys(): void
    {
        $payload = self::validPayload();

        self::assertArrayHasKey('battery_level', $payload);
        self::assertArrayHasKey('charge_enable_request', $payload);
        self::assertArrayHasKey('scheduled_charging_start_time', $payload);
        self::assertArrayHasKey('inside_temp', $payload);
        self::assertArrayHasKey('climate_keeper_mode', $payload);
        self::assertArrayHasKey('hvac_ac_enabled', $payload);
    }

    #[Test]
    public function validPayloadBatteryLevelIsANumber(): void
    {
        $payload = self::validPayload();
        self::assertTrue(is_int($payload['battery_level']) || is_float($payload['battery_level']));
    }

    #[Test]
    public function validPayloadChargeEnableRequestIsABoolean(): void
    {
        self::assertIsBool(self::validPayload()['charge_enable_request']);
    }

    #[Test]
    public function validPayloadScheduledChargingStartTimeIsAValidDate(): void
    {
        $value = self::validPayload()['scheduled_charging_start_time'];
        self::assertNotFalse(DateTime::createFromFormat('Y-m-d H:i:s', (string) $value));
    }

    #[Test]
    public function validPayloadInsideTempIsANumber(): void
    {
        $payload = self::validPayload();
        self::assertTrue(is_int($payload['inside_temp']) || is_float($payload['inside_temp']));
    }

    #[Test]
    public function validPayloadClimateKeeperModeIsAnInteger(): void
    {
        self::assertIsInt(self::validPayload()['climate_keeper_mode']);
    }

    #[Test]
    public function validPayloadHvacAcEnabledIsABoolean(): void
    {
        self::assertIsBool(self::validPayload()['hvac_ac_enabled']);
    }

    /* malformed */
    #[Test]
    public function malformedPayloadHasAllExpectedKeys(): void
    {
        $payload = self::malformedPayload();

        self::assertArrayHasKey('battery_level', $payload);
        self::assertArrayHasKey('charge_enable_request', $payload);
        self::assertArrayHasKey('scheduled_charging_start_time', $payload);
        self::assertArrayHasKey('inside_temp', $payload);
        self::assertArrayHasKey('climate_keeper_mode', $payload);
        self::assertArrayHasKey('hvac_ac_enabled', $payload);
    }

    #[Test]
    public function malformedPayloadBatteryLevel(): void
    {
        $payload = self::malformedPayload();
        self::assertFalse(is_int($payload['battery_level']) || is_float($payload['battery_level']));
    }

    #[Test]
    public function malformedPayloadScheduledChargingStartTime(): void
    {
        $value = self::malformedPayload()['scheduled_charging_start_time'];
        self::assertFalse(DateTime::createFromFormat('Y-m-d H:i:s', (string) $value));
    }

    #[Test]
    public function malformedPayloadHvacAcEnabled(): void
    {
        self::assertFalse(is_bool(self::malformedPayload()['hvac_ac_enabled']));
    }
}
