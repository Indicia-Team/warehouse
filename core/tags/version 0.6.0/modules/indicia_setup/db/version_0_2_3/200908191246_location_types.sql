ALTER TABLE locations ADD COLUMN location_type_id integer;
ALTER TABLE locations ADD CONSTRAINT fk_location_type FOREIGN KEY (location_type_id) REFERENCES termlists_terms (id)    ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN locations.location_type_id IS 'Identifies term describing the type of the location. Foreign key to the termlists_terms table.';

SELECT insert_term('Unknown', 'eng', null, 'indicia:location_types');
SELECT insert_term('Site', 'eng', null, 'indicia:location_types');
SELECT insert_term('Vice County', 'eng', null, 'indicia:location_types');
SELECT insert_term('City', 'eng', null, 'indicia:location_types');
SELECT insert_term('Town', 'eng', null, 'indicia:location_types');
SELECT insert_term('Village', 'eng', null, 'indicia:location_types');