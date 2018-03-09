
DROP INDEX IF EXISTS ix_occurrence_external_key;
CREATE UNIQUE INDEX ix_occurrence_external_key
   ON occurrences (external_key ASC NULLS LAST);