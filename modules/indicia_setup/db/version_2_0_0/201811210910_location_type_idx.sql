DROP INDEX IF EXISTS fki_location_type;
CREATE INDEX fki_location_type
    ON locations
    USING btree
    (location_type_id);