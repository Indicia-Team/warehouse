-- #slow script#
-- Index more efficient if combined with website ID.
DROP INDEX ix_cache_occurrences_functional_location_ids;

CREATE INDEX ix_cache_occurrences_functional_location_ids
  ON cache_occurrences_functional
  USING gin
  (location_ids, website_id);