DROP INDEX ix_cache_taxon_searchterms_searchterm;

CREATE INDEX ix_cache_taxon_searchterms_searchterm
  ON cache_taxon_searchterms
  USING btree
  (searchterm varchar_pattern_ops);