-- Index for extracting deletions into feeds, e.g. Elasticsearch.
CREATE INDEX IF NOT EXISTS ix_occurrence_updated_on_deletions ON occurrences(updated_on) WHERE deleted=true;