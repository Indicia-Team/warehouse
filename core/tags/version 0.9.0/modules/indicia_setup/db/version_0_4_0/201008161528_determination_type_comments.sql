COMMENT ON COLUMN determinations.determination_type IS 'Type of determination. Can be one of determination_type can be one of:
-- ''A'' : Considered correct;
-- ''B'' : Considered incorrect;
-- ''C'' : Correct;
-- ''I'' : Incorrect;
-- ''R'' : Requires confirmation;
-- ''U'' : Unconfirmed;
-- ''X'' : Unidentified;';

COMMENT ON COLUMN determinations.taxa_taxon_list_id_list IS 'Where this determination refers to a list of possible taxa, contains an array of the IDs of those taxa.';