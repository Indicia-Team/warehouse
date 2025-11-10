ALTER TABLE cache_occurrences_functional
ADD COLUMN IF NOT EXISTS dna_derived BOOLEAN DEFAULT FALSE NOT NULL;

COMMENT ON COLUMN cache_occurrences_functional.dna_derived IS 'Flag indicating if the occurrence is DNA derived and has metadata in the dna_occurrences table.';