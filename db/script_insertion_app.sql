------------------------------------------------------------------
--                           VERSION 3                          --
--              INSERTION DES DONNÉES DE RÉFÉRENCE               --
------------------------------------------------------------------

SET search_path TO app;

-- Reference data kept for the MCD (days live in the DayOfWeek PHP enum).
INSERT INTO day_of_week (name) VALUES
    ('Lundi'),
    ('Mardi'),
    ('Mercredi'),
    ('Jeudi'),
    ('Vendredi'),
    ('Samedi'),
    ('Dimanche');

-- The 5 Tesla model lines (4th VIN character); kept for the MCD.
INSERT INTO vehicle_models (vin_code, name) VALUES
    ('S', 'Model S'),
    ('3', 'Model 3'),
    ('X', 'Model X'),
    ('Y', 'Model Y'),
    ('C', 'Cybertruck');
