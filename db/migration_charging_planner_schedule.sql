-- Migration: align charging_planner with the planner pattern used by preconditioning.
--
-- Before: charging_planner has no per-plan activation switch and no link to the
--         charge schedule pushed to the vehicle.
-- After:  `enabled` toggles a plan without deleting it (defaults to TRUE), and
--         `tesla_schedule_id` stores the id returned by add_charge_schedule so the
--         schedule can be updated or removed on the vehicle later.

ALTER TABLE app.charging_planner
    ADD COLUMN enabled BOOLEAN NOT NULL DEFAULT TRUE;

ALTER TABLE app.charging_planner
    ADD COLUMN tesla_schedule_id BIGINT;
