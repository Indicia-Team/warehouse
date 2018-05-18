CREATE TABLE attribute_sets
(
  id serial NOT NULL,
  title character varying NOT NULL,
  description character varying,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attribute_sets PRIMARY KEY (id),
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
COMMENT ON COLUMN attribute_sets.title IS 'Description given for the set of attributes';
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

CREATE TABLE attribute_sets_websites
(
  id serial NOT NULL,
  attribute_set_id integer NOT NULL,
  website_id integer NOT NULL,
  restrict_to_survey_id integer,
  restrict_to_taxon_meaning_id integer,
  restrict_to_stage_term_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_attribute_sets_websites PRIMARY KEY (id),
  CONSTRAINT fk_attribute_sets_websites_attribute_set FOREIGN KEY (attribute_set_id)
      REFERENCES attribute_sets (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_websites_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_websites_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_websites_termlists_term FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_websites_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_attribute_sets_websites_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE attribute_sets_websites IS 'Table associating sets of taxon attributes with the website/survey combinations and taxon restrictions they apply to.';
COMMENT ON COLUMN attribute_sets_websites.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN attribute_sets_websites.attribute_set_id IS 'Foreign key to the attribute set that is linked to the website';
COMMENT ON COLUMN attribute_sets_websites.website_id IS 'Foreign key to the website linked to the attribute set';
COMMENT ON COLUMN attribute_sets_websites.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attribute sets that are only applicable to a given taxon, identifies the '
  'taxon.';
COMMENT ON COLUMN attribute_sets_websites.restrict_to_stage_term_meaning_id IS
  'Foreign key to the meanings table. For attribute sets that are only applicable to a given life stage, identifies the '
  'stage.';
COMMENT ON COLUMN attribute_sets_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN attribute_sets_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN attribute_sets_websites.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN attribute_sets_websites.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN attribute_sets_websites.deleted IS 'Has this record been deleted?';