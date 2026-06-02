<?php

/**
 * Controller responsible for displaying the vehicle dashboard
 **/
class DashboardController
{
    /* Database connection */
    private PDO $db;

    /**
     * Initializes the controller with a database connection
     **/
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Entry point of the dashboard
     * Retrieves the connected user, their vehicle and telemetry data, then loads the view
     **/
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        /* Redirect to login if no user is found in session */
        if (!$userId) {
            header('Location: /login');
            exit();
        }

        $token = $this->getAccessToken($userId);
        $vin = $this->getSelectedVin($userId);

        /* Redirect to vehicle selection if token or VIN is missing */
        if (!$token || !$vin) {
            header('Location: /vehicles');
            exit();
        }

        $data = $this->getTelemetryData($vin);

        require_once '../private/Views/dashboard.php';
    }

    /**
     * Retrieves and decrypts the Tesla OAuth2 access token for the given user
     * Returns null if no token is found
     * TODO: load TESLAPP_TOKEN_ENCRYPTION_KEY once auth is set up
     **/
    private function getAccessToken(int $userId): ?string
    {
        require_once '../private/Models/Shared/TokenCipher.php';

        $token = $this->db->prepare("SELECT access_token_encrypted, access_token_nonce
                                    FROM oauth2_token
                                    WHERE user_id = ?
                                    ORDER BY created_at DESC LIMIT 1");
        $token->execute([$userId]);
        $row = $token->fetch();

        if (!$row) {
            return null;
        }

        $cipher = new \Teslapp\Models\Shared\TokenCipher(
            base64_decode($_ENV['TESLAPP_TOKEN_ENCRYPTION_KEY']),
        );

        return $cipher->decrypt($row['access_token_encrypted'], $row['access_token_nonce']);
    }

    /**
     * Retrieves the VIN of the vehicle selected by the given user
     * Returns null if no vehicle is found
     **/
    private function getSelectedVin(int $userId): ?string
    {
        $selectVin = $this->db->prepare("SELECT vin
                                    FROM vehicles
                                    WHERE user_id = ? LIMIT 1");
        $selectVin->execute([$userId]);
        $row = $selectVin->fetch();

        return $row['vin'] ?? null;
    }

    /**
     * Retrieves the latest telemetry data for the given VIN from the fleet_telemetry schema
     **/
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
