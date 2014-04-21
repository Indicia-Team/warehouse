ALTER TABLE filters DROP CONSTRAINT IF EXISTS uc_filter_name;
DROP INDEX IF EXISTS ix_filter_name_unique;
CREATE UNIQUE INDEX ix_filter_name_unique ON filters (title , sharing , created_by_id) WHERE deleted=false;