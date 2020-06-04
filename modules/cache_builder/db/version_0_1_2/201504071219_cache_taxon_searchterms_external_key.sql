ALTER TABLE cache_taxon_searchterms
   ADD COLUMN external_key character varying;
COMMENT ON COLUMN cache_taxon_searchterms.external_key
  IS 'External identifier for the taxon.';