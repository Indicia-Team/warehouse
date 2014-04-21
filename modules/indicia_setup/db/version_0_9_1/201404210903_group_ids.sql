ALTER TABLE samples
   ADD COLUMN group_id integer;
COMMENT ON COLUMN samples.group_id IS 'Foreign key to the groups table. Identifies the recording group that this sample was posted into, if explicitly posted to a group.';

ALTER TABLE cache_occurrences
   ADD COLUMN group_id integer;

COMMENT ON COLUMN cache_occurrences.group_id IS 'Foreign key to the groups table. Identifies the recording group that this record was posted into, if explicitly posted to a group.';

