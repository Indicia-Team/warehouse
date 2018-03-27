ALTER TABLE survey_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN survey_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN survey_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE sample_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN sample_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN sample_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE occurrence_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN occurrence_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN occurrence_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE location_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN location_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN location_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE taxa_taxon_list_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN taxa_taxon_list_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN taxa_taxon_list_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE termlists_term_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN termlists_term_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN termlists_term_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';

ALTER TABLE person_attributes
   ADD COLUMN term_name character varying,
   ADD COLUMN term_identifier character varying;
COMMENT ON COLUMN person_attributes.term_name
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term name. Otherwise '
  'provide a brief alphanumeric only (with no spaces) version of the attribute name  to give it a unique identifier '
  'within the context of the survey dataset to make it easier to refer to in configuration.';
COMMENT ON COLUMN person_attributes.term_identifier
  IS 'If the attribute is linked to a standardised glossary such as Darwin Core then provide the term identifier, '
  'typically the URL to the term definition.';