------------------------------------------------------------------
--                          VERSION 10                          --
------------------------------------------------------------------

DROP TABLE IF EXISTS app.vehicles CASCADE;
DROP TABLE IF EXISTS app.users CASCADE;
DROP TABLE IF EXISTS app.oauth2_token CASCADE;
DROP TABLE IF EXISTS app.vehicle_models CASCADE;
DROP TABLE IF EXISTS app.paths CASCADE;
DROP TABLE IF EXISTS app.path_points CASCADE;
DROP TABLE IF EXISTS app.air_conditioning_planner CASCADE;
DROP TABLE IF EXISTS app.charging_planner CASCADE;
DROP TABLE IF EXISTS app.ac_plans CASCADE;
DROP TABLE IF EXISTS app.charging_plans CASCADE;
DROP TABLE IF EXISTS app.day_of_week CASCADE;
DROP TABLE IF EXISTS app.jwt CASCADE;
DROP TABLE IF EXISTS app.rate_limits CASCADE;

CREATE TABLE app.vehicle_models
(
    id   UUID,
    name VARCHAR(32) NOT NULL UNIQUE,

    CONSTRAINT pk_vehicle_models PRIMARY KEY (id)
);

CREATE TABLE app.users
(
    id         UUID,
    email      VARCHAR(320) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT now(),
    updated_at TIMESTAMP    NOT NULL DEFAULT now(),

    CONSTRAINT pk_user PRIMARY KEY (id),

    CONSTRAINT unique_email UNIQUE (email)
);

CREATE TABLE app.vehicles
(
    vin      VARCHAR(17),
    user_id  UUID        NOT NULL,
    name     VARCHAR(100) NOT NULL,
    model_id UUID        NOT NULL,

    CONSTRAINT pk_vehicles PRIMARY KEY (vin),

    CONSTRAINT fk_vehicles_vehicle_models FOREIGN KEY (model_id)
        REFERENCES app.vehicle_models (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vehicles_users FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE CASCADE
);

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

    created_at               TIMESTAMP DEFAULT now() NOT NULL,
    updated_at               TIMESTAMP DEFAULT now() NOT NULL,

    CONSTRAINT pk_oauth2_token PRIMARY KEY (id),

    -- One token row per user, so the OAuth material can be upserted on refresh.
    CONSTRAINT uq_oauth2_token_user UNIQUE (user_id),

    CONSTRAINT fk_oauth2_token_users FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE CASCADE
);

CREATE TABLE app.paths
(
    id                 UUID                    NOT NULL,
    vin                VARCHAR(17)             NOT NULL,
    starting_timestamp TIMESTAMP DEFAULT now() NOT NULL,
    arrival_timestamp  TIMESTAMP,
    starting_address   VARCHAR(255)            NOT NULL,
    arrival_address    VARCHAR(255)            NOT NULL,
    km_distance        DECIMAL,

    CONSTRAINT pk_path PRIMARY KEY (id),

    CONSTRAINT fk_path_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE TABLE app.path_points
(
    id                   UUID                    NOT NULL,
    path_id              UUID                    NOT NULL,
    latitude             DECIMAL,
    longitude            DECIMAL,
    path_point_timestamp TIMESTAMP DEFAULT now() NOT NULL,

    CONSTRAINT pk_path_point PRIMARY KEY (id),

    CONSTRAINT fk_path_point_path FOREIGN KEY (path_id)
        REFERENCES app.paths (id)
        ON DELETE CASCADE
);

CREATE TABLE app.air_conditioning_planner
(
    id                       UUID,
    vin                      VARCHAR(17) NOT NULL,
    activation_hour          TIME        NOT NULL,
    deactivate_after_success BOOLEAN,

    CONSTRAINT pk_air_conditioning_planner PRIMARY KEY (id),

    CONSTRAINT fk_air_conditioning_planner_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE TABLE app.charging_planner
(
    id                       UUID,
    vin                      VARCHAR(17) NOT NULL,
    activation_hour          TIME        NOT NULL,
    deactivation_hour        TIME,
    activation_latitude      DECIMAL,
    activation_longitude     DECIMAL,
    deactivate_after_success BOOLEAN,

    CONSTRAINT pk_charging_planner PRIMARY KEY (id),

    CONSTRAINT fk_charging_planner_vehicle FOREIGN KEY (vin)
        REFERENCES app.vehicles (vin)
        ON DELETE CASCADE
);

CREATE TABLE app.day_of_week
(
    id   INT GENERATED ALWAYS AS IDENTITY,
    name VARCHAR(8) NOT NULL UNIQUE,

    CONSTRAINT pk_day_of_week PRIMARY KEY (id)
);

CREATE TABLE app.ac_plans
(
    id     UUID,
    day_id INT,

    CONSTRAINT pk_ac_plans PRIMARY KEY (id, day_id),

    CONSTRAINT fk_ac_plans_air_conditioning_planner FOREIGN KEY (id)
        REFERENCES app.air_conditioning_planner (id)
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

CREATE TABLE app.rate_limits
(
    id         BIGINT GENERATED ALWAYS AS IDENTITY,
    key_hash   CHAR(64)  NOT NULL,           -- SHA-256 de (action + ip) ou (action + user_id)
    attempt_at TIMESTAMP NOT NULL DEFAULT now(),
    expires_at TIMESTAMP NOT NULL,

    CONSTRAINT pk_rate_limits PRIMARY KEY (id)
);

CREATE INDEX idx_rate_limits_key_expires ON app.rate_limits (key_hash, expires_at);