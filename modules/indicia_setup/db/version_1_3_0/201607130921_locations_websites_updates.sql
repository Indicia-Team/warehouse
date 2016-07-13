DROP TRIGGER IF EXISTS audit_trigger_row ON locations_websites;
DROP TRIGGER IF EXISTS audit_trigger_stm ON locations_websites;

ALTER TABLE locations_websites ADD COLUMN updated_on timestamp without time zone;
UPDATE locations_websites SET updated_on=created_on;
ALTER TABLE locations_websites ALTER COLUMN updated_on SET NOT NULL;
COMMENT ON COLUMN locations_websites.updated_on IS 'Date and time this record was updated.';

ALTER TABLE locations_websites ADD COLUMN updated_by_id integer;
COMMENT ON COLUMN locations_websites.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';

