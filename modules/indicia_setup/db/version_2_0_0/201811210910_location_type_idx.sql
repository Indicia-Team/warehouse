
DO $$
BEGIN

IF NOT EXISTS (
    SELECT 1
    FROM   pg_class c
    JOIN   pg_namespace n ON n.oid = c.relnamespace
    WHERE  c.relname = 'fki_location_type'
    ) THEN

    CREATE INDEX fki_location_type
    ON locations
    USING btree
    (location_type_id);
END IF;

END$$;

