ALTER TABLE cache_taxon_searchterms
  ADD COLUMN taxon_rank character varying;

COMMENT ON COLUMN cache_taxon_searchterms.taxon_rank
    IS 'Taxon rank given for this taxon, e.g. Species or Phylum.';