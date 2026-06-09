<?php
declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use Teslapp\Models\Climate\ValueObjects\KeeperMode;
use Teslapp\Models\Climate\ValueObjects\Temperature;
use Teslapp\Models\Shared\Exceptions\ClimateException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;
use Teslapp\Models\Shared\ValueObjects\Vin;

final class ClimateClient
{
    private string $baseUrl = 'https://fleet-api.prd.eu.vn.cloud.tesla.com';

    public function startClimate(Vin $vin, AccessToken $token, ?Temperature $temp = null): void
    {
        $this->send($vin, $token, 'auto_conditioning_start');

        if ($temp !== null) {
            $this->send($vin, $token, 'set_temps', [
                'driver_temp' => $temp->value,
                'passenger_temp' => $temp->value,
            ]);
        }
    }

    public function stopClimate(Vin $vin, AccessToken $token): void
    {
        $this->send($vin, $token, 'auto_conditioning_stop');
    }

    public function setKeeperMode(Vin $vin, AccessToken $token, KeeperMode $mode): void
    {
        $this->send($vin, $token, 'set_climate_keeper_mode', [
            'climate_keeper_mode' => $mode->value,
        ]);
    }

    private function send(Vin $vin, AccessToken $token, string $command, array $body = []): void
    {
        $url = "{$this->baseUrl}/api/1/vehicles/{$vin->value}/command/{$command}";
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token->value}",
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200)
        {
            throw new ClimateException("Tesla command '{$command}' failed (HTTP {$httpCode}).");
        }
    }
}