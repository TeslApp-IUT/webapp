------------------------------------------------------------------
--                           VERSION 5                          --
------------------------------------------------------------------

DROP TABLE IF EXISTS fleet_telemetry.charge_level CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_enable CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.charge_scheduled CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.temp_int CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.keeper_mode CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.ac_enabled CASCADE;
DROP TABLE IF EXISTS fleet_telemetry.location CASCADE;

CREATE TABLE fleet_telemetry.charge_level
(
    id            INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin           VARCHAR(17)                      NOT NULL,
    battery_level REAL                             NOT NULL, -- Niveau de la batterie
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

CREATE TABLE fleet_telemetry.temp_int
(
    id          INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin         VARCHAR(17)                      NOT NULL,
    inside_temp REAL                             NOT NULL, -- Température intérieure
    timestamp   TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_temp_int PRIMARY KEY (id),

    CONSTRAINT fk_temp_int_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_temp_int_vin_ts ON fleet_telemetry.temp_int (vin, timestamp DESC);

CREATE TABLE fleet_telemetry.keeper_mode
(
    id                  INT GENERATED ALWAYS AS IDENTITY NOT NULL,
    vin                 VARCHAR(17)                      NOT NULL,
    climate_keeper_mode INT                              NOT NULL, -- Mode Keeper
    timestamp           TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_keeper_mode PRIMARY KEY (id),

    CONSTRAINT fk_keeper_mode_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_keeper_mode_vin_ts ON fleet_telemetry.keeper_mode (vin, timestamp DESC);

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
    latitude  FLOAT                            NOT NULL,
    longitude FLOAT                            NOT NULL,
    timestamp TIMESTAMP DEFAULT now()          NOT NULL,

    CONSTRAINT pk_location PRIMARY KEY (id),

    CONSTRAINT fk_location_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE INDEX idx_location_vin_ts ON fleet_telemetry.location (vin, timestamp DESC);
