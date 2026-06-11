------------------------------------------------------------------
--                           VERSION 6                          --
------------------------------------------------------------------
-- Run AFTER script_creation_app.sql.

CREATE SCHEMA IF NOT EXISTS fleet_telemetry;

-- cube + earthdistance power the distance maths of the app.trips view.
CREATE SCHEMA IF NOT EXISTS extensions;
CREATE EXTENSION IF NOT EXISTS cube WITH SCHEMA extensions;
CREATE EXTENSION IF NOT EXISTS earthdistance WITH SCHEMA extensions;

-- Resolves the unqualified earth_distance()/ll_to_earth() calls below.
SET search_path TO app, fleet_telemetry, extensions;

DROP VIEW IF EXISTS app.trips;
DROP VIEW IF EXISTS app.overview;

DROP TABLE IF EXISTS fleet_telemetry.charge_level CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_enable CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_scheduled CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_limit_soc CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_current_request CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.temp_int CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.climate_keeper_mode CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.ac_enabled CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.location CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.drive_rail CASCADE;

CREATE TABLE fleet_telemetry.charge_level
(
    id            INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin           VARCHAR(17)                      NOT NULL,
    battery_level NUMERIC(5, 2)                    NOT NULL, -- Niveau de la batterie
    timestamp     TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_charge_level PRIMARY KEY (id),

    CONSTRAINT fk_charge_level_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_charge_level_vin_ts ON fleet_telemetry.charge_level (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.charge_enable
(
    id                    INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                   VARCHAR(17)                      NOT NULL,
    charge_enable_request BOOLEAN                          NOT NULL, -- Charge activée ?
    timestamp             TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_charge_enable PRIMARY KEY (id),

    CONSTRAINT fk_charge_enable_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_charge_enable_vin_ts ON fleet_telemetry.charge_enable (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.charge_scheduled
(
    id                            INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                           VARCHAR(17)                      NOT NULL,
    scheduled_charging_start_time TIMESTAMP                        NOT NULL, -- Date de la charge planifiée
    timestamp                     TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_charge_scheduled PRIMARY KEY (id),

    CONSTRAINT fk_charge_scheduled_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_charge_scheduled_vin_ts ON fleet_telemetry.charge_scheduled (vin, timestamp DESC);

-- Constraint/index names mirror the deployed table (created by the ingestion pipeline).
CREATE TABLE fleet_telemetry.charge_limit_soc
(
    id               INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin              VARCHAR(17)                      NOT NULL,
    charge_limit_soc INT NOT NULL DEFAULT 100,                  -- % at which charging stops
    timestamp        TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT charge_limit_soc_pk PRIMARY KEY (id),

    CONSTRAINT charge_limit_soc_vehicles_vin_fk FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE,

    CONSTRAINT check_within_bounds CHECK (charge_limit_soc >= 0 AND charge_limit_soc <= 100)
);

-- UNUSED: strict duplicate of idx_charge_limit_soc_vin_ts.
CREATE INDEX charge_limit_soc_vin_timestamp_index ON fleet_telemetry.charge_limit_soc (vin, timestamp DESC);
CREATE INDEX idx_charge_limit_soc_vin_ts ON fleet_telemetry.charge_limit_soc (vin, timestamp DESC);

-- Constraint/index names mirror the deployed table (created by the ingestion pipeline).
CREATE TABLE fleet_telemetry.charge_current_request
(
    id                     INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                    VARCHAR(17)                      NOT NULL,
    charge_current_request INT,                                      -- requested charging amps (nullable on the deployed table)
    timestamp              TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT charge_current_request_pk PRIMARY KEY (id),

    CONSTRAINT charge_current_request_vehicles_vin_fk FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE,

    CONSTRAINT check_positive CHECK (charge_current_request IS NULL OR charge_current_request >= 0)
);

-- Likely UNUSED ((timestamp, vin) serves no app query); confirm with the infra.
CREATE INDEX charge_current_request_timestamp_vin_index ON fleet_telemetry.charge_current_request (timestamp DESC, vin);
CREATE INDEX idx_charge_current_request_vin_ts ON fleet_telemetry.charge_current_request (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.temp_int
(
    id          INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin         VARCHAR(17)                      NOT NULL,
    inside_temp NUMERIC(5, 2)                    NOT NULL, -- Température intérieure
    timestamp   TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_temp_int PRIMARY KEY (id),

    CONSTRAINT fk_temp_int_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_temp_int_vin_ts ON fleet_telemetry.temp_int (vin, timestamp DESC);

-- Constraint/index names mirror the deployed table (the PK kept an index-style name).
CREATE TABLE fleet_telemetry.climate_keeper_mode
(
    id                  INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                 VARCHAR(17)                      NOT NULL,
    climate_keeper_mode INT                              NOT NULL, -- Mode Keeper
    timestamp           TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT climate_keeper_mode_id_uindex PRIMARY KEY (id),

    CONSTRAINT fk_keeper_mode_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX climate_keeper_mode_vin_timestamp_index ON fleet_telemetry.climate_keeper_mode (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.ac_enabled
(
    id              INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin             VARCHAR(17)                      NOT NULL,
    hvac_ac_enabled BOOLEAN                          NOT NULL, -- AC activé ?
    timestamp       TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_ac_enabled PRIMARY KEY (id),

    CONSTRAINT fk_ac_enabled_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_ac_enabled_vin_ts ON fleet_telemetry.ac_enabled (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.location
(
    id        INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin       VARCHAR(17)                      NOT NULL,
    latitude  NUMERIC(8, 6)                    NOT NULL,
    longitude NUMERIC(9, 6)                    NOT NULL,
    timestamp TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_location PRIMARY KEY (id),

    CONSTRAINT fk_location_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_location_vin_ts ON fleet_telemetry.location (vin, timestamp DESC);

-- Constraint/index names mirror the deployed table (created by the ingestion pipeline).
CREATE TABLE fleet_telemetry.drive_rail
(
    id         INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin        VARCHAR(17)                      NOT NULL,
    drive_rail BOOLEAN                          NOT NULL, -- Véhicule en conduite ?
    timestamp  TIMESTAMP,                                 -- nullable, no default (deployed table)

    CONSTRAINT drive_rail_pk PRIMARY KEY (id),

    CONSTRAINT drive_rail_vehicles_vin_fk FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

-- UNUSED: redundant with the PK (id alone is already unique).
CREATE UNIQUE INDEX drive_rail_id_vin_uindex ON fleet_telemetry.drive_rail (id DESC, vin);

-- Latest value of each telemetry signal, one row per VIN.
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
                             LIMIT 1) ccr ON true
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
                             LIMIT 1) loc ON true;

-- Trips rebuilt from the location stream (>50 m jump = new run, parked after 5 min idle).
CREATE VIEW app.trips AS
WITH points AS (
    SELECT l.id,
           l.vin,
           l.latitude,
           l.longitude,
           l."timestamp" AS ts,
           lag(l.latitude) OVER w  AS prev_lat,
           lag(l.longitude) OVER w AS prev_lon
    FROM fleet_telemetry.location l
    WINDOW w AS (PARTITION BY l.vin ORDER BY l."timestamp", l.id)
), runs AS (
    SELECT points.id,
           points.vin,
           points.latitude,
           points.longitude,
           points.ts,
           count(*) FILTER (WHERE points.prev_lat IS NULL
                               OR earth_distance(ll_to_earth(points.prev_lat::double precision, points.prev_lon::double precision),
                                                 ll_to_earth(points.latitude::double precision, points.longitude::double precision)) > 50::double precision)
               OVER (PARTITION BY points.vin ORDER BY points.ts, points.id) AS run_id
    FROM points
), run_stays AS (
    SELECT runs.vin,
           runs.run_id,
           min(runs.ts) AS run_start,
           COALESCE(lead(min(runs.ts)) OVER w, timezone('UTC'::text, now())) AS stay_end,
           CASE
               WHEN lead(min(runs.ts)) OVER w IS NULL THEN timezone('UTC'::text, now())
               ELSE max(runs.ts) + '00:00:00.000001'::interval
           END AS drive_check_until
    FROM runs
    GROUP BY runs.vin, runs.run_id
    WINDOW w AS (PARTITION BY runs.vin ORDER BY runs.run_id)
), parked_runs AS (
    SELECT rs.vin,
           rs.run_id,
           rs.run_start,
           (rs.stay_end - rs.run_start) >= '00:05:00'::interval
               AND NOT (EXISTS ( SELECT 1
                                 FROM fleet_telemetry.drive_rail dr
                                 WHERE dr.vin::text = rs.vin::text
                                   AND dr.drive_rail
                                   AND dr."timestamp" >= rs.run_start
                                   AND dr."timestamp" < rs.drive_check_until))
               AND COALESCE(( SELECT dr.drive_rail
                              FROM fleet_telemetry.drive_rail dr
                              WHERE dr.vin::text = rs.vin::text
                                AND dr."timestamp" < rs.drive_check_until
                              ORDER BY dr."timestamp" DESC
                              LIMIT 1), false) = false AS is_parked
    FROM run_stays rs
), run_trips AS (
    SELECT parked_runs.vin,
           parked_runs.run_id,
           parked_runs.run_start,
           parked_runs.is_parked,
           COALESCE(sum(parked_runs.is_parked::integer)
                        OVER (PARTITION BY parked_runs.vin ORDER BY parked_runs.run_id
                              ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0::bigint) AS trip_no
    FROM parked_runs
), trip_points AS (
    SELECT r.id,
           r.vin,
           rt.trip_no,
           rt.is_parked,
           r.latitude,
           r.longitude,
           r.ts,
           lag(r.latitude) OVER tw  AS prev_lat,
           lag(r.longitude) OVER tw AS prev_lon
    FROM runs r
             JOIN run_trips rt ON rt.vin::text = r.vin::text AND rt.run_id = r.run_id
    WHERE NOT rt.is_parked OR r.ts = rt.run_start
    WINDOW tw AS (PARTITION BY r.vin, rt.trip_no ORDER BY r.ts, r.id)
)
SELECT md5((vin::text || ':'::text) || (array_agg(id ORDER BY ts, trip_points.id))[1])::uuid AS id,
       vin,
       min(ts) AS start_time,
       max(ts) AS end_time,
       NOT bool_or(is_parked) AS running,
       (array_agg(id ORDER BY ts, trip_points.id))[1] AS start_location_id,
       (array_agg(id ORDER BY ts DESC, trip_points.id DESC))[1] AS end_location_id,
       (array_agg(latitude ORDER BY ts, trip_points.id))[1] AS start_latitude,
       (array_agg(longitude ORDER BY ts, trip_points.id))[1] AS start_longitude,
       (array_agg(latitude ORDER BY ts DESC, trip_points.id DESC))[1] AS end_latitude,
       (array_agg(longitude ORDER BY ts DESC, trip_points.id DESC))[1] AS end_longitude,
       COALESCE(sum(earth_distance(ll_to_earth(prev_lat::double precision, prev_lon::double precision),
                                   ll_to_earth(latitude::double precision, longitude::double precision))), 0::double precision) AS total_distance
FROM trip_points
GROUP BY vin, trip_no
HAVING count(*) > 1
ORDER BY vin, (min(ts));
