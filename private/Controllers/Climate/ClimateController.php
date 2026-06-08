<?php
declare(strict_types=1);

namespace Teslapp\Controllers\Climate;

use PDO;

/**
 * Controller responsible for climate-related commands
 * Sends climate commands to the Tesla Fleet API
 **/
class ClimateController
{
    private PDO $db;
    private string $baseUrl = 'https://fleet-api.prd.eu.vn.cloud.tesla.com';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * GET dashboard/ac
     * Displays the climate page
     **/
    public function ac(): void
    {
        $vin = $_SESSION['selected_vin'] ?? null;

        if (!$vin)
        {
            header('Location: /vehicle/select');
            exit();
        }

        require_once __DIR__ . '/../Views/Climate/ac.php';
    }

    /**
     * Handles climate activation and deactivation
     * When action is 'start', activates the climate and optionally sets the temperature
     * When action is 'stop', deactivates the climate
     * Redirects to the actual page
     **/
    public function toggle(): void
    {
        $vin = $_SESSION['selected_vin'] ?? null;
        $token = $_SESSION['access_token'] ?? null;

        if (!$vin || !$token)
        {
            header('Location: /vehicle/select');
            exit();
        }

        $action = filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW);

        if ($action === 'start')
        {
            $this->sendCommand($vin, $token, 'auto_conditioning_start');

            $temp = filter_input(INPUT_POST, 'temperature', FILTER_VALIDATE_FLOAT);
            if ($temp !== false && $temp >= 15.0 && $temp <= 28.0)
            {
                $this->sendCommand($vin, $token, 'set_temps', [
                    'driver_temp' => $temp,
                    'passenger_temp' => $temp,
                ]);
            }
        }
        elseif ($action === 'stop')
        {
            $this->sendCommand($vin, $token, 'auto_conditioning_stop');
        }

        header('Location: /dashboard/ac');
        exit();
    }

    /**
     * Sends a POST command to the Tesla Fleet API
     * Returns true if the command was accepted, false otherwise
     **/
    private function sendCommand(string $vin, string $token, string $command, array $body = []): bool
    {
        $url = "{$this->baseUrl}/api/1/vehicles/{$vin}/command/{$command}";
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
        ]);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * POST climate/keeper
     * Sets the climate keeper mode : 0 = Off, 1 = Keep, 2 = Dog, 3 = Camp
     **/
    public function setKeeperMode(): void
    {
        $vin = $_SESSION['selected_vin'] ?? null;
        $token = $_SESSION['access_token'] ?? null;

        if (!$vin || !$token)
        {
            header('Location: /vehicle/select');
            exit();
        }

        $mode = filter_input(INPUT_POST, 'climate_keeper_mode', FILTER_VALIDATE_INT);

        if ($mode === false || $mode < 0 || $mode > 3)
        {
            header('Location: /dashboard/ac');
            exit();
        }

        $this->sendCommand($vin, $token, 'set_climate_keeper_mode', [
            'climate_keeper_mode' => $mode,
        ]);

        header('Location: /dashboard/ac');
        exit();
    }
}