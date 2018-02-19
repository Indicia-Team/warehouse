ALTER TABLE survey_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN survey_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE sample_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN sample_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE occurrence_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN occurrence_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE location_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN location_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE taxa_taxon_list_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN taxa_taxon_list_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE termlists_term_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN termlists_term_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';

ALTER TABLE person_attributes
   ADD COLUMN caption_i18n jsonb;
COMMENT ON COLUMN person_attributes.caption_i18n
  IS 'Stores a list of localised versions of the caption keyed by language code.';