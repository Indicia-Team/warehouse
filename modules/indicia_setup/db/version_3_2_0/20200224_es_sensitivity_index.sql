-- Improves performance when extracting sensitive data to move to Elasticsearch.
CREATE INDEX IF NOT EXISTS ix_cache_occ_functional_id_tracking_sens
ON cache_occurrences_functional(id, tracking) WHERE sensitive=true;