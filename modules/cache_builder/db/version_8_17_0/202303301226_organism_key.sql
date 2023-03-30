ALTER TABLE cache_taxa_taxon_lists
  ADD COLUMN organism_key varchar;

COMMENT ON COLUMN cache_taxa_taxon_lists.organism_key
    IS 'Identifier for the organism concept, e.g. when linking to UKSI.';

ALTER TABLE cache_taxon_searchterms
  ADD COLUMN organism_key varchar;

COMMENT ON COLUMN cache_taxon_searchterms.organism_key
    IS 'Identifier for the organism concept, e.g. when linking to UKSI.';