------------------------------------------------------------------
--                           VERSION 2                          --
--              INSERTION DES DONNÉES DE RÉFÉRENCE               --
------------------------------------------------------------------

SET search_path TO app;

INSERT INTO day_of_week (name) VALUES
    ('Lundi'),
    ('Mardi'),
    ('Mercredi'),
    ('Jeudi'),
    ('Vendredi'),
    ('Samedi'),
    ('Dimanche');

-- The 5 Tesla model lines, keyed by their VIN discriminator (4th VIN character).
INSERT INTO vehicle_models (vin_code, name) VALUES
    ('S', 'Model S'),
    ('3', 'Model 3'),
    ('X', 'Model X'),
    ('Y', 'Model Y'),
    ('C', 'Cybertruck');
