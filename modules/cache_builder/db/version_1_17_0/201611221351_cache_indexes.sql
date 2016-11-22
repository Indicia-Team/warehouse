-- #slow script#

CREATE INDEX ix_cache_taxa_taxon_lists_external_key
  ON cache_taxa_taxon_lists
  USING btree
  (external_key);

CREATE INDEX ix_cache_occurrences_functional_status
  ON cache_occurrences_functional
  USING btree
  (record_status, record_substatus);

DROP INDEX ix_cache_occurrences_functional_record_status;