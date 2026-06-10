-- Migration: restore the planner FK on preconditioning_plans (deployed database drift).
--
-- script_creation_app.sql declares fk_preconditioning_plans_preconditioning_planner
-- (ON DELETE CASCADE) but the deployed table predates it (its PK is still named
-- pk_ac_plans), so deleting a preconditioning plan leaves its day rows orphaned —
-- PreconditioningPlannerRepository::deleteById() relies on the cascade.

-- Remove any orphaned day rows first, otherwise the constraint cannot be added.
DELETE FROM app.preconditioning_plans
WHERE id NOT IN (SELECT id FROM app.preconditioning_planner);

ALTER TABLE app.preconditioning_plans
    ADD CONSTRAINT fk_preconditioning_plans_preconditioning_planner FOREIGN KEY (id)
        REFERENCES app.preconditioning_planner (id)
        ON DELETE CASCADE;
