-- Migration: decouple vehicle rows from users by nullifying user_id instead of deleting.
--
-- Before: removing a vehicle (e.g. sync with Tesla API) deleted the row, which cascaded into
--         all telemetry, path, and planner tables.
-- After:  removing a vehicle sets user_id = NULL; the VIN row and all child data survive.
--
-- The FK on users → vehicles is also updated to ON DELETE SET NULL so that deleting a user
-- automatically detaches their vehicles rather than deleting them.

-- 1. Allow user_id to be NULL (was NOT NULL).
ALTER TABLE app.vehicles
    ALTER COLUMN user_id DROP NOT NULL;

-- 2. Replace the ON DELETE CASCADE FK with ON DELETE SET NULL.
ALTER TABLE app.vehicles
    DROP CONSTRAINT fk_vehicles_users;

ALTER TABLE app.vehicles
    ADD CONSTRAINT fk_vehicles_users
        FOREIGN KEY (user_id)
        REFERENCES app.users (id)
        ON DELETE SET NULL;
