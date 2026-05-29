------------------------------------------------------------------
--                           VERSION 1                          --
--              INSERTION DES DONNÉES DE RÉFÉRENCE               --
------------------------------------------------------------------

INSERT INTO day_of_week (name) VALUES
    ('Lundi'),
    ('Mardi'),
    ('Mercredi'),
    ('Jeudi'),
    ('Vendredi'),
    ('Samedi'),
    ('Dimanche');

INSERT INTO vehicle_models (id, name) VALUES
    (gen_random_uuid(), 'Model S'),
    (gen_random_uuid(), 'Model 3'),
    (gen_random_uuid(), 'Model X'),
    (gen_random_uuid(), 'Model Y'),
    (gen_random_uuid(), 'Cybertruck');
