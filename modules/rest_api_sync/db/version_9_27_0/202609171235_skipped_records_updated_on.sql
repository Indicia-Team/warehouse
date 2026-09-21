ALTER TABLE rest_api_sync_skipped_records
  ADD COLUMN updated_on timestamp without time zone,
  ADD COLUMN updated_by_id integer,
  ADD CONSTRAINT fk_rest_api_sync_skipped_record_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;
UPDATE rest_api_sync_skipped_records SET updated_on=created_on, updated_by_id=created_by_id;
ALTER TABLE rest_api_sync_skipped_records ALTER COLUMN updated_on SET NOT NULL;
COMMENT ON COLUMN rest_api_sync_skipped_records.updated_on IS 'Date and time this record was updated.';
COMMENT ON COLUMN rest_api_sync_skipped_records.updated_by_id IS 'User ID of the person who last updated this record.';