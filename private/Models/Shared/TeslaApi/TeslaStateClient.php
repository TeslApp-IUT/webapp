<?php
declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\VehicleConnectivityStatus;
use Teslapp\Models\Vehicle\Vehicle;

final readonly class TeslaStateClient implements VehicleStateClient
{
    public function __construct() { }

    /**
     * @return Vehicle[]
     **/
    public function listVehicles(AccessToken $token): array
    {
        $body = TeslaHttpClient::get('/api/1/vehicles', $token);
        $response = $body['response'] ?? [];

        if (!is_array($response))
        {
            return [];
        }

        $vehicles = [];
        foreach ($response as $row)
        {
            if (is_array($row))
            {
                $vehicles[] = Vehicle::fromTeslaResponse($row);
            }
        }

        return $vehicles;
    }

    /**
     * @return array<string, VehicleConnectivityStatus>
     **/
    public function fetchConnectivity(AccessToken $token): array
    {
        $body = TeslaHttpClient::get('/api/1/vehicles', $token);
        $response = $body['response'] ?? [];

        if (!is_array($response))
        {
            return [];
        }

        $statuses = [];

        foreach ($response as $row)
        {
            if (is_array($row) && isset($row['vin']))
            {
                $statuses[(string) $row['vin']] = VehicleConnectivityStatus::fromApiState(
                    (string) ($row['state'] ?? 'unknown'),
                );
            }
        }

        return $statuses;
    }
}