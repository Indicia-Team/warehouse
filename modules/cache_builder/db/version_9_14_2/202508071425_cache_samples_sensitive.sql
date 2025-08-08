CREATE TABLE IF NOT EXISTS cache_samples_sensitive (
  id integer NOT NULL,
  location_ids integer[],
  PRIMARY KEY (id)
);

COMMENT ON TABLE cache_samples_sensitive IS 'Table to store sample data for the full-precision copy of sensitive samples added to Elasticsearch.';