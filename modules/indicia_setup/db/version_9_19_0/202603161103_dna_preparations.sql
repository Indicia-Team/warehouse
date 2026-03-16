ALTER TABLE dna_occurrences
  ADD COLUMN preparations text[];

COMMENT ON COLUMN dna_occurrences.preparations IS 'A list of preparations and preservation methods.';