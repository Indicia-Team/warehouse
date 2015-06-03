

ALTER TABLE summariser_definitions
  ADD COLUMN check_for_missing BOOLEAN NOT NULL DEFAULT 'f',
  ADD COLUMN max_records_per_cycle INTEGER NOT NULL DEFAULT 1000;

COMMENT ON COLUMN summariser_definitions.check_for_missing IS 'Enable checking for missed occurrences on this survey (can be switched off for performance reasons)';
COMMENT ON COLUMN summariser_definitions.max_records_per_cycle IS 'The maximum number of occurrence records processed per survey per invocation of the scheduled task.';
