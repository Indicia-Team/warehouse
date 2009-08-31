ALTER TABLE websites
   ADD COLUMN url character varying(500);

-- set a default for existing data.
UPDATE websites SET url='unknown';

ALTER TABLE websites
   ALTER COLUMN url SET NOT NULL;

COMMENT ON COLUMN websites.url IS 'URL of the website root.';