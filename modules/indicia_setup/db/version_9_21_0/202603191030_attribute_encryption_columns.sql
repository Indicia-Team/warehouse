-- Add encryption support flag to custom attribute definitions.
ALTER TABLE location_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE occurrence_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE sample_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE person_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE survey_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE taxa_taxon_list_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;
ALTER TABLE termlists_term_attributes
  ADD COLUMN IF NOT EXISTS encrypt boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN location_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN occurrence_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN sample_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN person_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN survey_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN taxa_taxon_list_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
COMMENT ON COLUMN termlists_term_attributes.encrypt IS 'When true, text custom attribute values are encrypted before storage.';
