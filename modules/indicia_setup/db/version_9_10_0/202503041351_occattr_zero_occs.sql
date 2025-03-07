ALTER TABLE occurrence_attributes_websites
ADD COLUMN auto_handle_zero_abundance BOOLEAN DEFAULT FALSE;

COMMENT ON COLUMN occurrence_attributes_websites.auto_handle_zero_abundance IS
  'If set to true, then occurrence.zero_abundance is set to true if a value of 0, absent, none, not present or zero detected for this attribute in this survey.';