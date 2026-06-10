-- Migration: cache reverse-geocoded addresses so a coordinate is resolved once.
--
-- Before: the navigation page reverse-geocodes every trip's start and end point
--         against Nominatim on each load, which is slow and rate-limited.
-- After:  resolved labels are stored keyed by their (rounded) coordinates; a hit
--         skips the HTTP call entirely. Lat/lon use the same NUMERIC precision as
--         the planner tables (6 decimals ~= 0.11 m), which also rounds on insert.

CREATE TABLE app.geocode_cache
(
    latitude   NUMERIC(8, 6) NOT NULL,
    longitude  NUMERIC(9, 6) NOT NULL,
    label      VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT now(),

    CONSTRAINT pk_geocode_cache PRIMARY KEY (latitude, longitude)
);
