ALTER TABLE indicia.cache_taxon_searchterms
   ADD COLUMN external_key character varying;
COMMENT ON COLUMN indicia.cache_taxon_searchterms.external_key
  IS 'External identifier for the taxon.';

UPDATE cache_taxon_searchterms cts
SET external_key = cttl.external_key
FROM cache_taxa_taxon_lists cttl
WHERE cttl.id=cts.taxa_taxon_list_id;