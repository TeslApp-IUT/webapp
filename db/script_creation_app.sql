------------------------------------------------------------------
--                          VERSION 17                          --
------------------------------------------------------------------
-- Execution order: 1) this file  2) script_creation_telemetry.sql  3) script_insertion_app.sql

CREATE SCHEMA IF NOT EXISTS app;

-- No `public` schema on the deployed database.
DROP SCHEMA IF EXISTS public;

ALTER DATABASE teslapp SET search_path TO app, fleet_telemetry, extensions;

-- Views depending on app.vehicles; recreated by script_creation_telemetry.sql.
DROP VIEW IF EXISTS app.trips;
DROP VIEW IF EXISTS app.overview;

DROP TABLE IF EXISTS app.remember_tokens CASCADE;
DROP TABLE IF EXISTS app.vehicles CASCADE;
DROP TABLE IF EXISTS app.users CASCADE;
DROP TABLE IF EXISTS app.oauth2_token CASCADE;
DROP TABLE IF EXISTS app.vehicle_models CASCADE;
DROP TABLE IF EXISTS app.preconditioning_planner CASCADE;
DROP TABLE IF EXISTS app.charging_planner CASCADE;
DROP TABLE IF EXISTS app.preconditioning_plans CASCADE;
DROP TABLE IF EXISTS app.charging_plans CASCADE;
DROP TABLE IF EXISTS app.day_of_week CASCADE;
DROP TABLE IF EXISTS app.jwt CASCADE;
DROP TABLE IF EXISTS app.rate_limits CASCADE;
DROP TABLE IF EXISTS app.geocode_cache CASCADE;

-- UNUSED by the code (model resolved in PHP); kept for the MCD.
CREATE TABLE app.vehicle_models
(
    name     VARCHAR(32) NOT NULL UNIQUE,
    vin_code CHAR(1)     NOT NULL,  -- 4th VIN character: 'S', '3', 'X', 'Y', 'C'

    CONSTRAINT pk_vehicle_models PRIMARY KEY (vin_code)
);

CREATE TABLE app.users
(
    id           UUID,
    email        VARCHAR(320)  NOT NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT now(),  -- UNUSED: filled by DEFAULT, never read
    updated_at   TIMESTAMP     NOT NULL DEFAULT now(),
    first_name   VARCHAR(100),
    last_name    VARCHAR(100),
    avatar_url   VARCHAR(2048) DEFAULT NULL,
    is_developer BOOLEAN       NOT NULL DEFAULT false,

    CONSTRAINT pk_user PRIMARY KEY (id),

    CONSTRAINT unique_email UNIQUE (email)
);

CREATE TABLE app.vehicles
(
    vin        VARCHAR(17),
    user_id    UUID,                   -- NULL = detached vehicle (gone from the Tesla account)
    name       VARCHAR(100) NOT NULL,
    model_code CHAR(1)      NOT NULL,  -- 4th VIN character, FK to vehicle_models
    public_id  UUID         NOT NULL DEFAULT gen_random_uuid(),  -- stable random id used in URLs (the VIN stays private)

    CONSTRAINT pk_vehicles PRIMARY KEY (vin),

    CONSTRAINT uq_vehicles_public_id UNIQUE (public_id),

    -- No ON DELETE CASCADE: deleting a reference model must never wipe vehicles.
    CONSTRAINT fk_vehicles_vehicle_models FOREIGN KEY (model_code)
        REFERENCES app.vehicle_models (vin_code),

    CONSTRAINT fk_vehicles_users FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE SET NULL
);

-- Write-only: upserted at each login, never read back (OIDC audit trail).
CREATE TABLE app.jwt
(
    id         UUID        NOT NULL,
    iss        VARCHAR(64) NOT NULL,
    sub        UUID UNIQUE NOT NULL,
    aud        UUID        NOT NULL,
    auth_time  TIMESTAMP   NOT NULL,
    exp        TIMESTAMP   NOT NULL,
    iat        TIMESTAMP   NOT NULL,
    updated_at TIMESTAMP   NOT NULL,

    CONSTRAINT pk_jwt PRIMARY KEY (id),

    -- The Tesla `sub` claim is the user identity; it maps directly to users.id.
    CONSTRAINT fk_jwt_users FOREIGN KEY (sub)
        REFERENCES app.users (id)
        ON DELETE CASCADE
);

CREATE TABLE app.oauth2_token
(
    id                       UUID,
    user_id                  UUID                    NOT NULL,

    access_token_encrypted   TEXT                    NOT NULL,
    access_token_nonce       TEXT                    NOT NULL,
    access_token_expired_at  TIMESTAMP               NOT NULL,

    refresh_token_encrypted  TEXT                    NOT NULL,
    refresh_token_nonce      TEXT                    NOT NULL,
    refresh_token_expired_at TIMESTAMP               NOT NULL,

    created_at               TIMESTAMP DEFAULT now() NOT NULL,  -- UNUSED: filled by DEFAULT, never read (freshness uses updated_at)
    updated_at               TIMESTAMP DEFAULT now() NOT NULL,

    CONSTRAINT pk_oauth2_token PRIMARY KEY (id),

    -- One token row per user, so the OAuth material can be upserted on refresh.
    CONSTRAINT uq_oauth2_token_user UNIQUE (user_id),

    CONSTRAINT fk_oauth2_token_users FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE CASCADE
);

CREATE TABLE app.remember_tokens
(
    id         UUID      NOT NULL DEFAULT gen_random_uuid(),  -- UNUSED: all lookups/deletes go via token_hash or user_id (token_hash could be the PK)
    user_id    UUID      NOT NULL,
    token_hash CHAR(64)  NOT NULL,   -- SHA-256 of the raw cookie value
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT now(),  -- UNUSED: filled by DEFAULT, never read

    CONSTRAINT pk_remember_tokens PRIMARY KEY (id),
    CONSTRAINT uq_remember_tokens_hash UNIQUE (token_hash),

    CONSTRAINT fk_remember_tokens_users FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE CASCADE
);

CREATE INDEX idx_remember_tokens_user ON app.remember_tokens (user_id);

CREATE TABLE app.preconditioning_planner
(
    id                       UUID,
    vin                      VARCHAR(17) NOT NULL,
    activation_hour          TIME        NOT NULL,
    deactivate_after_success BOOLEAN,
    enabled                  BOOLEAN     NOT NULL DEFAULT TRUE,
    activation_latitude      NUMERIC(8, 6),
    activation_longitude     NUMERIC(9, 6),
    location_label           VARCHAR(255),
    tesla_schedule_id        BIGINT,

    CONSTRAINT pk_preconditioning_planner PRIMARY KEY (id),

    CONSTRAINT fk_preconditioning_planner_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE TABLE app.charging_planner
(
    id                       UUID,
    vin                      VARCHAR(17) NOT NULL,
    activation_hour          TIME        NOT NULL,
    deactivation_hour        TIME,
    activation_latitude      NUMERIC(8, 6),
    activation_longitude     NUMERIC(9, 6),
    deactivate_after_success BOOLEAN,
    location_label           VARCHAR(255),
    enabled                  BOOLEAN     NOT NULL DEFAULT TRUE,
    tesla_schedule_id        BIGINT,

    CONSTRAINT pk_charging_planner PRIMARY KEY (id),

    CONSTRAINT fk_charging_planner_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

-- UNUSED by the code (DayOfWeek PHP enum); kept for the MCD.
CREATE TABLE app.day_of_week
(
    id   INT GENERATED ALWAYS AS IDENTITY,
    name VARCHAR(8) NOT NULL UNIQUE,

    CONSTRAINT pk_day_of_week PRIMARY KEY (id)
);

-- pk_ac_plans / fk_ac_plans_day_of_week: legacy names from the deployed database.
CREATE TABLE app.preconditioning_plans
(
    id     UUID,
    day_id INT,

    CONSTRAINT pk_ac_plans PRIMARY KEY (id, day_id),

    CONSTRAINT fk_preconditioning_plans_preconditioning_planner FOREIGN KEY (id)
        REFERENCES app.preconditioning_planner (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ac_plans_day_of_week FOREIGN KEY (day_id)
        REFERENCES app.day_of_week (id)
        ON DELETE CASCADE
);

CREATE TABLE app.charging_plans
(
    id     UUID,
    day_id INT,

    CONSTRAINT pk_charging_plans PRIMARY KEY (id, day_id),

    CONSTRAINT fk_charging_plans_charging_planner FOREIGN KEY (id)
        REFERENCES app.charging_planner (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_charging_plans_day_of_week FOREIGN KEY (day_id)
        REFERENCES app.day_of_week (id)
        ON DELETE CASCADE
);

-- UNUSED by the code (no rate-limiting implemented); kept for the MCD.
CREATE TABLE app.rate_limits
(
    id         BIGINT GENERATED ALWAYS AS IDENTITY,
    key_hash   CHAR(64)  NOT NULL,           -- SHA-256 de (action + ip) ou (action + user_id)
    attempt_at TIMESTAMP NOT NULL DEFAULT now(),
    expires_at TIMESTAMP NOT NULL,

    CONSTRAINT pk_rate_limits PRIMARY KEY (id)
);

CREATE INDEX idx_rate_limits_key_expires ON app.rate_limits (key_hash, expires_at);

-- Reverse-geocoding cache: a coordinate is resolved against Nominatim only once.
CREATE TABLE app.geocode_cache
(
    latitude     NUMERIC(8, 6) NOT NULL,
    longitude    NUMERIC(9, 6) NOT NULL,
    label        VARCHAR(255)  NOT NULL,
    full_address TEXT,
    created_at   TIMESTAMP     NOT NULL DEFAULT now(),  -- UNUSED: filled by DEFAULT, never read

    CONSTRAINT pk_geocode_cache PRIMARY KEY (latitude, longitude)
);
