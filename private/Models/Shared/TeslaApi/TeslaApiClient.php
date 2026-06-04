<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use JsonException;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Vehicle\Vehicle;

/**
 * Tesla Fleet API client over HTTP (cURL). Targets the regional Fleet API base URL.
 */
final readonly class TeslaApiClient implements VehicleStateClient
{
    public function __construct(
        private string $baseUrl,
        private int    $timeoutSeconds = 10,
    ) {}

    /**
     * @return Vehicle[]
     *
     * @throws TeslaApiException
     */
    public function listVehicles(AccessToken $token): array
    {
        $body = $this->get('/api/1/vehicles', $token);

        $response = $body['response'] ?? [];
        if (!is_array($response)) {
            return [];
        }

        $vehicles = [];
        foreach ($response as $row) {
            if (is_array($row)) {
                $vehicles[] = Vehicle::fromTeslaResponse($row);
            }
        }

        return $vehicles;
    }

    /**
     * @return array<string, VehicleConnectivityStatus> VIN => live status
     *
     * @throws TeslaApiException
     */
    public function fetchConnectivity(AccessToken $token): array
    {
        $body = $this->get('/api/1/vehicles', $token);

        $response = $body['response'] ?? [];
        if (!is_array($response)) {
            return [];
        }

        $statuses = [];
        foreach ($response as $row) {
            if (is_array($row) && isset($row['vin'])) {
                $statuses[(string) $row['vin']] = VehicleConnectivityStatus::fromApiState(
                    (string) ($row['state'] ?? 'unknown'),
                );
            }
        }

        return $statuses;
    }

    /**
     * Performs a GET request and returns the decoded JSON body.
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    private function get(string $path, AccessToken $token): array
    {
        $ch = curl_init($this->baseUrl . $path);
        if ($ch === false) {
            throw new TeslaApiException("Could not initialise the HTTP request for GET $path.");
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token->value,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // The token is never included in any error message (it must not leak into logs).
        if (!is_string($response)) {
            throw new TeslaApiException("Tesla API network error on GET $path: $error");
        }

        if ($status >= 400) {
            throw new TeslaApiException("Tesla API returned HTTP $status on GET $path.");
        }

        try {
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TeslaApiException(
                "Tesla API returned invalid JSON on GET $path.",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new TeslaApiException(
                "Tesla API returned an unexpected JSON shape on GET $path.",
            );
        }

        return $decoded;
    }
}
