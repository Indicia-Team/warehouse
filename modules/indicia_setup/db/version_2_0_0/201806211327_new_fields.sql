ALTER TABLE survey_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE sample_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE occurrence_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE location_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE taxa_taxon_list_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE termlists_term_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;
ALTER TABLE person_attributes
  ADD COLUMN unit character varying,
  ADD COLUMN description_i18n json,
  ADD COLUMN image_path character varying;

COMMENT ON COLUMN survey_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm.';
COMMENT ON COLUMN survey_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN survey_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN sample_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN sample_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN sample_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN occurrence_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN occurrence_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN occurrence_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN location_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN location_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN location_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN taxa_taxon_list_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN taxa_taxon_list_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN taxa_taxon_list_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN termlists_term_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN termlists_term_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN termlists_term_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';
COMMENT ON COLUMN person_attributes.unit IS 'Name of the attribute\s unit where relevant, e.g. mm';
COMMENT ON COLUMN person_attributes.description_i18n
  IS 'Stores a list of localised versions of the description keyed by language code.';
COMMENT ON COLUMN person_attributes.image_path IS
  'Path to an image file representing the attribute, e.g. an explanatory diagram.';

ALTER TABLE termlists_terms
  ADD COLUMN image_path character varying;
COMMENT ON COLUMN termlists_terms.image_path IS
  'Path to an image file representing the term, e.g. an explanatory diagram.';