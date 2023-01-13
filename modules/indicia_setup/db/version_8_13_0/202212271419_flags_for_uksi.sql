ALTER TABLE taxa
  ADD COLUMN organism_deprecated boolean default false,
  ADD COLUMN name_deprecated boolean default false,
  ADD COLUMN name_form char default 'W';

COMMENT ON COLUMN taxa.organism_deprecated IS 'Captures the state of any flag indicating redundancy or deprecation of the organism in the database the name was sourced from. Not actually used in Indicia but ensures that the allow_data_entry flag does get used to overwrite the intentions of the data source unintentionally.';
COMMENT ON COLUMN taxa.name_deprecated IS 'Captures the state of any flag indicating redundancy or deprecation of the individual name in the database the name was sourced from. Not actually used in Indicia but ensures that the allow_data_entry flag does get used to overwrite the intentions of the data source unintentionally.';
COMMENT ON COLUMN taxa.name_form IS 'Captures the state of any flag indicating that the name is identified as ill-formed in the database the name was sourced from. Not actually used in Indicia but ensures that the allow_data_entry flag does get used to overwrite the intentions of the data source unintentionally.';