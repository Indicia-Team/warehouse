ALTER TABLE locations ADD COLUMN public boolean;
ALTER TABLE locations ALTER COLUMN public SET DEFAULT false;
COMMENT ON COLUMN locations.public IS 'Flag set to true if this location is available for use by any website. If false the location is only available for use by the websites in the locations_websites table.';
