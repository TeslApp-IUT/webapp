------------------------------------------------------------------
-- MIGRATION (sans perte de données)
--   vehicle_models : id (UUID)  -> vin_code (CHAR 1, clé naturelle)
--   vehicles       : model_id   -> model_code (CHAR 1)
--
-- À exécuter UNE FOIS sur une base DÉJÀ peuplée (prod, ou dev existant)
-- pour éviter le DROP+RECREATE de script_creation_app.sql.
-- Transactionnel : si une étape échoue, tout est annulé (ROLLBACK), aucune
-- donnée perdue. Sur une base vierge, utiliser script_creation_app.sql.
------------------------------------------------------------------

SET search_path TO app;

BEGIN;

-- 1. vehicle_models : nouvelle clé naturelle, remplie depuis le nom du modèle.
ALTER TABLE vehicle_models ADD COLUMN vin_code CHAR(1);

UPDATE vehicle_models SET vin_code = CASE name
    WHEN 'Model S'    THEN 'S'
    WHEN 'Model 3'    THEN '3'
    WHEN 'Model X'    THEN 'X'
    WHEN 'Model Y'    THEN 'Y'
    WHEN 'Cybertruck' THEN 'C'
END;

-- 2. vehicles : nouvelle colonne, remplie via le modèle actuellement référencé
--    (équivaut au 4e caractère du VIN, mais on reprend la référence existante).
ALTER TABLE vehicles ADD COLUMN model_code CHAR(1);

UPDATE vehicles v
SET model_code = m.vin_code
FROM vehicle_models m
WHERE v.model_id = m.id;

-- 3. Bascule des contraintes : FK puis PK (la FK doit partir avant la PK).
ALTER TABLE vehicles       DROP CONSTRAINT fk_vehicles_vehicle_models;
ALTER TABLE vehicle_models DROP CONSTRAINT pk_vehicle_models;

ALTER TABLE vehicle_models ALTER COLUMN vin_code SET NOT NULL;
ALTER TABLE vehicle_models ADD CONSTRAINT pk_vehicle_models PRIMARY KEY (vin_code);

ALTER TABLE vehicles       ALTER COLUMN model_code SET NOT NULL;
ALTER TABLE vehicles       ADD CONSTRAINT fk_vehicles_vehicle_models
    FOREIGN KEY (model_code) REFERENCES vehicle_models (vin_code);

-- 4. Suppression des anciennes colonnes UUID.
ALTER TABLE vehicles       DROP COLUMN model_id;
ALTER TABLE vehicle_models DROP COLUMN id;

COMMIT;
