ALTER TABLE cache_taxa_taxon_lists ADD COLUMN identification_difficulty integer;

COMMENT ON COLUMN cache_taxa_taxon_lists.identification_difficulty IS
  'Identification difficulty assigned by the data_cleaner module, on a scale from 1 (easy) to 5 (difficult)';