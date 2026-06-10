<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Charging\ValueObjects\ChargeLimit;
use Teslapp\Models\Charging\ValueObjects\ChargingAmps;
use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API adapter for the charging commands and charge schedules.
 * Requests go through TeslaHttpClient, which resolves the access token from the session.
 *
 * While $dryRun is true (the default, wired from TESLA_COMMANDS_DRY_RUN) no command is
 * actually sent, so the UI works without touching a real vehicle.
 */
final readonly class TeslaChargingClient implements ChargingCommandClient
{
    public function __construct(private bool $dryRun = true) {}

    public function startCharging(Vin $vin): void
    {
        $this->post("/api/1/vehicles/$vin->value", []);
    }

    public function stopCharging(Vin $vin): void
    {
        $this->post("/api/1/vehicles/$vin->value", []);
    }

    public function setChargeLimit(Vin $vin, ChargeLimit $limit): void
    {
        $this->post("/api/1/vehicles/$vin->value", [
            'percent' => $limit->value,
        ]);
    }

    public function setChargingAmps(Vin $vin, ChargingAmps $amps): void
    {
        $this->post("/api/1/vehicles/$vin->value", [
            'charging_amps' => $amps->value,
        ]);
    }

    public function addChargeSchedule(
        Vin $vin,
        int $startTimeMinutes,
        ?int $endTimeMinutes,
        string $daysOfWeekCsv,
        bool $enabled,
        bool $oneTime,
        float $lat,
        float $lon,
        ?string $name = null,
        ?int $scheduleId = null,
    ): ?int {
        // Field names mirror add_precondition_schedule (CSV days, lat/lon) — the format
        // is to be confirmed against the proxy on the next real-vehicle session.
        $body = [
            'days_of_week' => $daysOfWeekCsv,
            'start_enabled' => true,
            'start_time' => $startTimeMinutes,
            'end_enabled' => $endTimeMinutes !== null,
            'enabled' => $enabled,
            'one_time' => $oneTime,
            'lat' => $lat,
            'lon' => $lon,
        ];

        if ($endTimeMinutes !== null) {
            $body['end_time'] = $endTimeMinutes;
        }

        if ($name !== null) {
            $body['name'] = $name;
        }

        if ($scheduleId !== null) {
            $body['id'] = $scheduleId;
        }

        $response =
            $this->post("/api/1/vehicles/$vin->value", $body) ?? [];

        $inner = $response['response'] ?? [];
        $id = is_array($inner) ? $inner['id'] ?? null : null;

        return is_numeric($id) ? (int) $id : null;
    }

    public function removeChargeSchedule(Vin $vin, int $scheduleId): void
    {
        $this->post("/api/1/vehicles/$vin->value", [
            'id' => $scheduleId,
        ]);
    }

    /**
     * Sends the command, unless the dry-run guard is active (then logs and returns null).
     *
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null the decoded Tesla response, or null in dry-run
     */
    private function post(string $path, array $body): ?array
    {
        if ($this->dryRun) {
            error_log("TESLA_COMMANDS_DRY_RUN active — command not sent: $path");

            return null;
        }

        return TeslaHttpClient::post($path, $body);
    }
}
