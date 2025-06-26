CREATE INDEX IF NOT EXISTS ix_occurrence_verified_by_id ON occurrences
  (verified_by_id)
  WHERE verified_by_id IS NOT NULL;