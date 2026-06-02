/*
------------------------------------------------------------------
--                           VERSION 2                          --
------------------------------------------------------------------
*/

const VIN = process.argv[2];
const ACCESS_TOKEN = process.argv[3];
const BASE_URL = 'https://fleet-api.prd.eu.vn.cloud.tesla.com';

const body = {
  vins: [VIN],

  config: {
    hostname: 'telemetry.teslapp.feyli.dev',
    port: 4445,

    delivery_policy: 'latest',

    exp: Math.floor(Date.now() / 1000) + 86400 * 30,

    fields: {
      // Battery
      BatteryLevel: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },
      ChargeEnableRequest: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },
      ScheduledChargingStartTime: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },

      // Climate
      InsideTemp: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },
      ClimateKeeperMode: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },
      HvacACEnabled: {
        interval_seconds: 10,
        minimum_delta: 1,
        resend_interval_seconds: 3600,
      },
    },

    ca: process.env.TESLA_CA_CERT,
  },
};

async function startTelemetry() {
  try {
    const response = await fetch(`${BASE_URL}/api/1/vehicles/fleet_telemetry_config`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${ACCESS_TOKEN}`,
        'Content-Type': 'application/json',
      },

      body: JSON.stringify(body),
    });

    const data = await response.json();

    console.log('Status:', response.status);
    console.log(JSON.stringify(data, null, 2));
  } catch (err) {
    console.error(err);
  }
}

startTelemetry();
