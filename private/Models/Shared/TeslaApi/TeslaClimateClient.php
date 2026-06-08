<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\Vin;

/**
 * Tesla Fleet API adapter for the preconditioning schedule commands.
 * The requests go through TeslaHttpClient, which also handles the access token.
 */
final readonly class TeslaClimateClient implements ClimateCommandClient
{
    public function addPreconditionSchedule(
        Vin $vin,
        int $preconditionTimeMinutes,
        string $daysOfWeekCsv,
        bool $enabled,
        bool $oneTime,
        float $lat,
        float $lon,
        ?int $scheduleId = null,
    ): array {
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

        return TeslaHttpClient::post(
            "/api/1/vehicles/{$vin->value}/command/add_precondition_schedule",
            $body,
        );
    }

    public function removePreconditionSchedule(Vin $vin, int $scheduleId): void
    {
        TeslaHttpClient::post(
            "/api/1/vehicles/{$vin->value}/command/remove_precondition_schedule",
            ['id' => $scheduleId],
        );
    }
}
