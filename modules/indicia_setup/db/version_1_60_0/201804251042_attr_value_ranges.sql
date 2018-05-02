ALTER TABLE survey_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE sample_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE occurrence_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE location_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE taxa_taxon_list_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE termlists_term_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;
ALTER TABLE person_attributes
  ADD COLUMN allow_ranges boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN survey_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN sample_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN occurrence_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN location_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN taxa_taxon_list_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN termlists_term_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';
COMMENT ON COLUMN person_attributes.allow_ranges IS 'Set to true if numeric attributes allow a value range to be entered.';

ALTER TABLE survey_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE sample_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE occurrence_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE location_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE taxa_taxon_list_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE termlists_term_attribute_values
  ADD COLUMN upper_value float;
ALTER TABLE person_attribute_values
  ADD COLUMN upper_value float;

COMMENT ON COLUMN survey_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN sample_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN occurrence_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN location_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN taxa_taxon_list_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN termlists_term_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';
COMMENT ON COLUMN person_attribute_values.upper_value IS 'If the attribute allows value ranges, then provides the upper value of the range.';