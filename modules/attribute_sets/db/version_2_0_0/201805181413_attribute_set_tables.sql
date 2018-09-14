CREATE TABLE attribute_sets
(
  id serial NOT NULL,
  title character varying NOT NULL,
  description character varying,
  website_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attribute_sets PRIMARY KEY (id),
  CONSTRAINT fk_attribute_set_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_set_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_set_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE attribute_sets IS 'Table listing groups of custom attributes that are managed together';
COMMENT ON COLUMN attribute_sets.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN attribute_sets.title IS 'Title given for the set of attributes';
COMMENT ON COLUMN attribute_sets.description IS 'Description given for the set of attributes';
COMMENT ON COLUMN attribute_sets.website_id IS 'Foreign key to the websites table, identifies the website that owns this attribute set for permissions purposes.';
COMMENT ON COLUMN attribute_sets.created_on IS 'Date this record was created.';
COMMENT ON COLUMN attribute_sets.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN attribute_sets.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN attribute_sets.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN attribute_sets.deleted IS 'Has this record been deleted?';

CREATE TABLE attribute_sets_taxa_taxon_list_attributes
(
  id serial NOT NULL,
  attribute_set_id integer NOT NULL,
  taxa_taxon_list_attribute_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attribute_sets_taxa_taxon_list_attributes PRIMARY KEY (id),
  CONSTRAINT fk_attribute_sets_taxa_taxon_list_attributes_attribute_set FOREIGN KEY (attribute_set_id)
      REFERENCES attribute_sets (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_taxa_taxon_list_attributes_taxa_taxon_list_attribute FOREIGN KEY (taxa_taxon_list_attribute_id)
      REFERENCES taxa_taxon_list_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_taxa_taxon_list_attributes_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_taxa_taxon_list_attributes_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE attribute_sets_taxa_taxon_list_attributes IS 'Table joining taxon attributes to the set they belong to';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.attribute_set_id IS 'Foreign key to the attribute set that holds the attribute';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.taxa_taxon_list_attribute_id IS 'Foreign key to the attribute held by the attribute set';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN attribute_sets_taxa_taxon_list_attributes.deleted IS 'Has this record been deleted?';

CREATE UNIQUE INDEX ix_unique_attribute_set_ttl_attribute
  ON attribute_sets_taxa_taxon_list_attributes (attribute_set_id, taxa_taxon_list_attribute_id)
  WHERE (deleted=false);

CREATE TABLE attribute_sets_surveys
(
  id serial NOT NULL,
  attribute_set_id integer NOT NULL,
  survey_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attribute_sets_surveys PRIMARY KEY (id),
  CONSTRAINT fk_attribute_sets_surveys_attribute_set FOREIGN KEY (attribute_set_id)
      REFERENCES attribute_sets (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_surveys_survey FOREIGN KEY (survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_surveys_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_surveys_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE attribute_sets_surveys IS 'Table associating sets of taxon attributes with the surveys they apply to.';
COMMENT ON COLUMN attribute_sets_surveys.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN attribute_sets_surveys.attribute_set_id IS 'Foreign key to the attribute set that is linked to the website';
COMMENT ON COLUMN attribute_sets_surveys.survey_id IS 'Foreign key to the survey that the attribute set is linked to.';
COMMENT ON COLUMN attribute_sets_surveys.created_on IS 'Date this record was created.';
COMMENT ON COLUMN attribute_sets_surveys.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN attribute_sets_surveys.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN attribute_sets_surveys.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN attribute_sets_surveys.deleted IS 'Has this record been deleted?';

CREATE UNIQUE INDEX ix_unique_attribute_set_survey
  ON attribute_sets_surveys (attribute_set_id, survey_id)
  WHERE (deleted=false);

CREATE TABLE attribute_sets_taxon_restrictions
(
  id serial NOT NULL,
  attribute_sets_survey_id integer,
  restrict_to_taxon_meaning_id integer,
  restrict_to_stage_term_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attributes_sets_taxon_restrictions PRIMARY KEY (id),
  CONSTRAINT fk_attributes_sets_taxon_restrictions_attr_sets_website FOREIGN KEY (attribute_sets_survey_id)
      REFERENCES attribute_sets_surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_taxon_restrictions_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_taxon_restrictions_termlists_term FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT attribute_sets_surveys_taxon_restrictions_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT attribute_sets_taxon_restrictions_updater FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE attribute_sets_taxon_restrictions IS
  'Lists any taxonomic or life stage restrictions for an occurrence attribute usage within a survey dataset. For '
  'example an attribute might only be enabled for moth records or for plants in flower. If no restrictions are listed '
  'for an attribute usage then it applies to all records in the dataset.';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.attribute_sets_survey_id IS
  'Foreign key to the attribute_sets_surveys table. Identifies the attribute usage scenario this restriction '
  'applies to';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the '
  'taxon.';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.restrict_to_stage_term_meaning_id IS
  'Foreign key to the meanings table. For attributes that are only applicable to a given life stage, identifies the '
  'stage.';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.created_on IS 'Date this record was created.';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN attribute_sets_taxon_restrictions.deleted IS 'Has this list been deleted?';

CREATE UNIQUE INDEX ix_unique_attribute_set_taxon_restriction
  ON attribute_sets_taxon_restrictions (attribute_sets_survey_id, restrict_to_taxon_meaning_id, restrict_to_stage_term_meaning_id)
  WHERE (deleted=false);