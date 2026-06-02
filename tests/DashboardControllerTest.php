<?php
require_once __DIR__ . '/../private/Controllers/DashboardController.php';

$mockData = [
    'battery_level' => 72,
    'charge_enable_request' => true,
    'scheduled_charging_start_time' => '2026-05-29 10:47:00',
    'inside_temp' => 21.5,
    'climate_keeper_mode' => 1,
    'hvac_ac_enabled' => true,
];

$mockData2 = [
    'battery_level' => null,
    'charge_enable_request' => false,
    'scheduled_charging_start_time' => 'b',
    'inside_temp' => 56,
    'climate_keeper_mode' => 0,
    'hvac_ac_enabled' => '',
];

function testTelemetryDataKeys(array $data): void
{
    $expectedKeys = [
        'battery_level',
        'charge_enable_request',
        'scheduled_charging_start_time',
        'inside_temp',
        'climate_keeper_mode',
        'hvac_ac_enabled',
    ];

    foreach ($expectedKeys as $key)
    {
        if (!array_key_exists($key, $data))
        {
            echo "Missing key: {$key}\n";
            return;
        }
    }

    echo "All keys are present\n";
}

function testTelemetryDataTypes(array $data): void
{
    /* Battery */
    if (!is_int($data['battery_level']) && !is_float($data['battery_level']))
    {
        echo "battery_level must be a number\n";
    }
    else
    {
        echo "battery_level is a number\n";
    }

    if (!is_bool($data['charge_enable_request']))
    {
        echo "charge_enable_request must be a boolean\n";
    }
    else
    {
        echo "charge_enable_request is a boolean\n";
    }

    if ($data['scheduled_charging_start_time'] !== null &&
        !is_int($data['scheduled_charging_start_time']) &&
        !DateTime::createFromFormat('Y-m-d H:i:s', $data['scheduled_charging_start_time']))
    {
        echo "scheduled_charging_start_time must be a valid date\n";
    }
    else
    {
        echo "scheduled_charging_start_time is valid\n";
    }

    /* Clim */
    if (!is_int($data['inside_temp']) && !is_float($data['inside_temp']))
    {
        echo "inside_temp must be a real (temperature)\n";
    }
    else
    {
        echo "inside_temp is a real (temperature)\n";
    }

    if (!is_int($data['climate_keeper_mode']))
    {
        echo "climate_keeper_mode must be an integer\n";
    }
    else
    {
        echo "climate_keeper_mode is an integer\n";
    }

    if (!is_bool($data['hvac_ac_enabled']))
    {
        echo "hvac_ac_enabled must be a boolean\n";
    }
    else
    {
        echo "hvac_ac_enabled is a boolean\n";
    }
}

/* First test all correct */
echo "DashboardController Tests #1 \n\n";
testTelemetryDataKeys($mockData);
echo "\n";
testTelemetryDataTypes($mockData);

/* Second test with error */
echo "\n\nDashboardController Tests #2 \n\n";
testTelemetryDataKeys($mockData2);
echo "\n";
testTelemetryDataTypes($mockData2);