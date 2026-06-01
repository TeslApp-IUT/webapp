const VIN = process.argv[2];
const ACCESS_TOKEN = process.argv[3];
const TELEMETRY_URL = 'telemetry.teslapp.feyli.dev:4445';

// Config télémétrie
const telemetryConfig = {
  connection_url: TELEMETRY_URL,
  max_retries: 3,

  fields: {
    // Battery
    BatteryLevel: 10000,
    ChargeEnableRequest: 10000,
    ScheduledChargingStartTime: 10000,

    // Climate
    InsideTemp: 10000,
    ClimateKeeperMode: 10000,
    HvacACEnabled: 10000,
  },
};

async function startTelemetry() {
  try {
    const url = `https://fleet-api.prd.eu.vn.cloud.tesla.com/api/1/vehicles/${VIN}/fleet_telemetry_config`;

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${ACCESS_TOKEN}`,
        'Content-Type': 'application/json',
      },

      body: JSON.stringify(telemetryConfig),
    });

    const data = await response.json();

    console.log('configuration envoyée avec succès');
    console.log('réponse de tesla :', data);
  } catch (error) {
    console.error(error.message);
  }
}

startTelemetry();
