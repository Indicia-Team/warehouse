DROP INDEX IF EXISTS ix_cache_occurrences_sample_id;
CREATE INDEX ix_cache_occurrences_sample_id ON cache_occurrences(sample_id);