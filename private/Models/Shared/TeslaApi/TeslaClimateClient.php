<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API adapter for the preconditioning schedule commands.
 * Requests go through TeslaHttpClient, which resolves the access token from the session.
 *
 * While $dryRun is true (the default, wired from TESLA_COMMANDS_DRY_RUN) no command is
 * actually sent, so the UI works without touching a real vehicle.
 */
final readonly class TeslaClimateClient implements ClimateCommandClient
{
    public function __construct(private bool $dryRun = true) {}

    public function addPreconditionSchedule(
        Vin $vin,
        int $preconditionTimeMinutes,
        string $daysOfWeekCsv,
        bool $enabled,
        bool $oneTime,
        float $lat,
        float $lon,
        ?int $scheduleId = null,
    ): ?int {
        $body = [
            'days_of_week' => $daysOfWeekCsv,
            'precondition_time' => $preconditionTimeMinutes,
            'enabled' => $enabled,
            'one_time' => $oneTime,
            'lat' => $lat,
            'lon' => $lon,
        ];

        if ($scheduleId !== null) {
            $body['id'] = $scheduleId;
        }

        $response = $this->post("/api/1/vehicles/$vin->value", $body) ?? [];

        $inner = $response['response'] ?? [];
        $id = is_array($inner) ? $inner['id'] ?? null : null;

        return is_numeric($id) ? (int) $id : null;
    }

    public function removePreconditionSchedule(Vin $vin, int $scheduleId): void
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
