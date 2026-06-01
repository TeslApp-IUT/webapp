<?php

class DashboardController
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $token = $this->getAccessToken($userId);
        $vin = $this->getSelectedVin($userId);

        if (!$token || !$vin) {
            header('Location: /vehicles');
            exit;
        }

        $data = $this->getTelemetryData($vin);

        require_once '../private/Views/dashboard.php';
    }

    private function getAccessToken(int $userId): ?string
    {
        $token = $this->db->prepare("SELECT access_token_encrypted
                                    FROM oauth2_token
                                    WHERE user_id = ?
                                    ORDER BY created_at DESC LIMIT 1");
        $token->execute([$userId]);

        $row = $token->fetch();
        return $row['access_token_encrypted'] ?? null;
    }

    private function getSelectedVin(int $userId): ?string
    {
        $selectVin = $this->db->prepare("SELECT vin
                                    FROM vehicles
                                    WHERE user_id = ? LIMIT 1");
        $selectVin->execute([$userId]);
        $row = $selectVin->fetch();

        return $row['vin'] ?? null;
    }

    private function getTelemetryData(string $vin): array
    {
        /* Battery */
        $battery = $this->db->prepare("SELECT battery_level
                                FROM fleet_telemetry.charge_level
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $battery->execute([$vin]);
        $row = $battery->fetch();
        $battery_level = $row['battery_level'] ?? null;

        $chargeEnable = $this->db->prepare("SELECT charge_enable_request
                                FROM fleet_telemetry.charge_enable
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $chargeEnable->execute([$vin]);
        $row = $chargeEnable->fetch();
        $charge_enable_request = $row['charge_enable_request'] ?? null;


        $scheduledCharge = $this->db->prepare("SELECT scheduled_charging_start_time
                                FROM fleet_telemetry.charge_scheduled
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $scheduledCharge->execute([$vin]);
        $row = $scheduledCharge->fetch();
        $scheduled_charging_start_time = $row['scheduled_charging_start_time'] ?? null;

        /* Clim */
        $temp = $this->db->prepare("SELECT inside_temp
                                FROM fleet_telemetry.temp_int
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $temp->execute([$vin]);
        $row = $temp->fetch();
        $inside_temp = $row['inside_temp'] ?? null;

        $keeper = $this->db->prepare("SELECT climate_keeper_mode
                                FROM fleet_telemetry.keeper_mode
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $keeper->execute([$vin]);
        $row = $keeper->fetch();
        $climate_keeper_mode = $row['climate_keeper_mode'] ?? null;


        $ac = $this->db->prepare("SELECT hvac_ac_enabled
                                FROM fleet_telemetry.ac_enabled
                                WHERE vin = ?
                                ORDER BY timestamp DESC
                                LIMIT 1");
        $ac->execute([$vin]);
        $row = $ac->fetch();
        $hvac_ac_enabled = $row['hvac_ac_enabled'] ?? null;

        return [
            /* Battery */
            'battery_level' => $battery_level,
            'charge_enable_request' => $charge_enable_request,
            'scheduled_charging_start_time' => $scheduled_charging_start_time,

            /* Clim */
            'inside_temp' => $inside_temp,
            'climate_keeper_mode' => $climate_keeper_mode,
            'hvac_ac_enabled' => $hvac_ac_enabled,
        ];
    }
}