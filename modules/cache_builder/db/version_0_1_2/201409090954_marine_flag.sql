ALTER TABLE cache_taxa_taxon_lists ADD COLUMN marine_flag boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN cache_taxa_taxon_lists.marine_flag IS 'Set to true for marine species.';

ALTER TABLE cache_taxon_searchterms ADD COLUMN marine_flag boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN cache_taxon_searchterms.marine_flag IS 'Set to true for marine species.';

