ALTER TABLE cache_taxon_searchterms ADD COLUMN authority character varying;
COMMENT ON COLUMN cache_taxon_searchterms.authority IS
  'The taxonomic authority of the name.';
