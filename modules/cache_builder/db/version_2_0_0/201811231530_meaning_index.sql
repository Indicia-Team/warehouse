-- #slow script#
DROP INDEX IF EXISTS ix_cache_occurrences_functional_taxon_meaning_id;

CREATE INDEX ix_cache_occurrences_functional_taxon_meaning_id
  ON cache_occurrences_functional
  USING btree
  (taxon_meaning_id);