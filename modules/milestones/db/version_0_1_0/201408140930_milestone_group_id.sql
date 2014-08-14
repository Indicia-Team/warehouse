ALTER TABLE milestones
   ADD COLUMN group_id integer;
COMMENT ON COLUMN milestones.group_id IS 'Foreign key to the groups table. Identifies the recording group that this milestone is associated with.';
