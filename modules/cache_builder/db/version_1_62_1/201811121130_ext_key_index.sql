CREATE INDEX ix_taxon_searchterms_external_key
  ON cache_taxon_searchterms
  USING btree
  (external_key);