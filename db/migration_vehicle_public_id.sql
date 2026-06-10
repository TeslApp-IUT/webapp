-- Adds a stable, random public identifier to vehicles, used in dashboard URLs
-- instead of the VIN (which is considered private information).
-- The DEFAULT ensures all existing rows receive a unique UUID automatically.
ALTER TABLE app.vehicles
    ADD COLUMN public_id UUID NOT NULL DEFAULT gen_random_uuid();

ALTER TABLE app.vehicles
    ADD CONSTRAINT uq_vehicles_public_id UNIQUE (public_id);
