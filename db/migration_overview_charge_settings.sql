-- Migration: expose the charge limit and requested amps in app.overview.
--
-- Before: the battery page hardcoded 80% / 16A as form defaults, with no way to
--         show the vehicle's actual settings (a car charging to 100% still showed
--         an 80% marker). The ChargeLimitSoc and ChargeCurrentRequest signals are
--         already streamed and ingested into fleet_telemetry.charge_limit_soc and
--         fleet_telemetry.charge_current_request, but app.overview did not expose
--         them.
-- After:  app.overview carries charge_limit and charge_current (latest value per
--         VIN, same LATERAL pattern as the other signals). The battery page
--         initialises its controls from the vehicle's real state.
--
-- ⚠ Ownership: app.overview is managed with the ingestion pipeline (infra side);
--   it was redeployed with these columns on 2026-06-11 while this migration was
--   being written. This file documents the definition now live on the deployed
--   database so a database rebuilt from db/ can recreate it — coordinate with the
--   infra before changing the view.
--
-- Note: both tables already exist on the deployed database (created with the
--       ingestion pipeline, outside db/ scripts — like drive_rail and
--       climate_keeper_mode). The CREATE TABLE IF NOT EXISTS below only matters
--       for a database rebuilt from the db/ scripts.

CREATE TABLE IF NOT EXISTS fleet_telemetry.charge_limit_soc
(
    id               INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin              VARCHAR(17)                      NOT NULL,
    charge_limit_soc INT                              NOT NULL, -- % at which charging stops
    timestamp        TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_charge_limit_soc PRIMARY KEY (id),

    CONSTRAINT fk_charge_limit_soc_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_charge_limit_soc_vin_ts
    ON fleet_telemetry.charge_limit_soc (vin, timestamp DESC);

CREATE TABLE IF NOT EXISTS fleet_telemetry.charge_current_request
(
    id                     INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                    VARCHAR(17)                      NOT NULL,
    charge_current_request INT                              NOT NULL, -- requested charging amps
    timestamp              TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_charge_current_request PRIMARY KEY (id),

    CONSTRAINT fk_charge_current_request_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_charge_current_request_vin_ts
    ON fleet_telemetry.charge_current_request (vin, timestamp DESC);

-- Definition live on the deployed database (2026-06-11). Replacing an existing
-- view whose columns moved requires DROP + CREATE, hence no CREATE OR REPLACE.
DROP VIEW IF EXISTS app.overview;

CREATE VIEW app.overview AS
SELECT v.vin,
       dr.drive_rail              AS driving,
       round(ti.inside_temp, 1)   AS inside_temp,
       ac.hvac_ac_enabled         AS ac_enabled,
       ce.charge_enable_request   AS charge_enable,
       round(cl.battery_level)    AS battery_level,
       cs.scheduled_charging_start_time,
       cls.charge_limit_soc       AS charge_limit,
       ccr.charge_current_request AS charge_current,
       ckm.climate_keeper_mode,
       loc.latitude,
       loc.longitude,
       GREATEST(dr."timestamp", ti."timestamp", ac."timestamp", ce."timestamp",
                cl."timestamp", cs."timestamp", ckm."timestamp", loc."timestamp") AS last_seen_at
FROM app.vehicles v
         LEFT JOIN LATERAL ( SELECT drive_rail.drive_rail,
                                    drive_rail."timestamp"
                             FROM fleet_telemetry.drive_rail
                             WHERE drive_rail.vin::text = v.vin::text
                             ORDER BY drive_rail."timestamp" DESC
                             LIMIT 1) dr ON true
         LEFT JOIN LATERAL ( SELECT temp_int.inside_temp,
                                    temp_int."timestamp"
                             FROM fleet_telemetry.temp_int
                             WHERE temp_int.vin::text = v.vin::text
                             ORDER BY temp_int."timestamp" DESC
                             LIMIT 1) ti ON true
         LEFT JOIN LATERAL ( SELECT ac_enabled.hvac_ac_enabled,
                                    ac_enabled."timestamp"
                             FROM fleet_telemetry.ac_enabled
                             WHERE ac_enabled.vin::text = v.vin::text
                             ORDER BY ac_enabled."timestamp" DESC
                             LIMIT 1) ac ON true
         LEFT JOIN LATERAL ( SELECT charge_enable.charge_enable_request,
                                    charge_enable."timestamp"
                             FROM fleet_telemetry.charge_enable
                             WHERE charge_enable.vin::text = v.vin::text
                             ORDER BY charge_enable."timestamp" DESC
                             LIMIT 1) ce ON true
         LEFT JOIN LATERAL ( SELECT charge_level.battery_level,
                                    charge_level."timestamp"
                             FROM fleet_telemetry.charge_level
                             WHERE charge_level.vin::text = v.vin::text
                             ORDER BY charge_level."timestamp" DESC
                             LIMIT 1) cl ON true
         LEFT JOIN LATERAL ( SELECT charge_scheduled.scheduled_charging_start_time,
                                    charge_scheduled."timestamp"
                             FROM fleet_telemetry.charge_scheduled
                             WHERE charge_scheduled.vin::text = v.vin::text
                             ORDER BY charge_scheduled."timestamp" DESC
                             LIMIT 1) cs ON true
         LEFT JOIN LATERAL ( SELECT climate_keeper_mode.climate_keeper_mode,
                                    climate_keeper_mode."timestamp"
                             FROM fleet_telemetry.climate_keeper_mode
                             WHERE climate_keeper_mode.vin::text = v.vin::text
                             ORDER BY climate_keeper_mode."timestamp" DESC
                             LIMIT 1) ckm ON true
         LEFT JOIN LATERAL ( SELECT location.latitude,
                                    location.longitude,
                                    location."timestamp"
                             FROM fleet_telemetry.location
                             WHERE location.vin::text = v.vin::text
                             ORDER BY location."timestamp" DESC
                             LIMIT 1) loc ON true
         LEFT JOIN LATERAL ( SELECT charge_limit_soc.charge_limit_soc,
                                    charge_limit_soc."timestamp"
                             FROM fleet_telemetry.charge_limit_soc
                             WHERE charge_limit_soc.vin::text = v.vin::text
                             ORDER BY charge_limit_soc."timestamp" DESC
                             LIMIT 1) cls ON true
         LEFT JOIN LATERAL ( SELECT charge_current_request.charge_current_request,
                                    charge_current_request."timestamp"
                             FROM fleet_telemetry.charge_current_request
                             WHERE charge_current_request.vin::text = v.vin::text
                             ORDER BY charge_current_request."timestamp" DESC
                             LIMIT 1) ccr ON true;
