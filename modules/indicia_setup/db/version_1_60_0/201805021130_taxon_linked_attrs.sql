CREATE TABLE occurrence_attribute_taxon_restrictions
(
  id serial NOT NULL,
  occurrence_attributes_website_id integer,
  restrict_to_taxon_meaning_id integer,
  restrict_to_stage_term_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_occurrence_attributes_taxon_restrictions PRIMARY KEY (id),
  CONSTRAINT fk_occurrence_attributes_taxon_restrictions_occurrence_attributes_website FOREIGN KEY (occurrence_attributes_website_id)
      REFERENCES occurrence_attributes_websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_attributes_taxon_restrictions_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_attributes_taxon_restrictions_termlists_term FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_updater FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE occurrence_attribute_taxon_restrictions IS
  'Lists any taxonomic or life stage restrictions for an occurrence attribute usage within a survey dataset. For '
  'example an attribute might only be enabled for moth records or for plants in flower. If no restrictions are listed '
  'for an attribute usage then it applies to all records in the dataset.';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.occurrence_attributes_website_id IS
  'Foreign key to the occurrence_attributes_websites table. Identifies the attribute usage scenario this restriction '
  'applies to';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the '
  'taxon.';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.restrict_to_stage_term_meaning_id IS
  'Foreign key to the meanings table. For attributes that are only applicable to a given life stage, identifies the '
  'stage.';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_attribute_taxon_restrictions.deleted IS 'Has this list been deleted?';

CREATE TABLE sample_attribute_taxon_restrictions
(
  id serial NOT NULL,
  sample_attributes_website_id integer,
  restrict_to_taxon_meaning_id integer,
  restrict_to_stage_term_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_sample_attribute_taxon_restrictions PRIMARY KEY (id),
  CONSTRAINT fk_sample_attribute_taxon_restrictions_sample_attributes_website FOREIGN KEY (sample_attributes_website_id)
      REFERENCES sample_attributes_websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_attribute_taxon_restrictions_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_attribute_taxon_restrictions_termlists_term FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_updater FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE sample_attribute_taxon_restrictions IS
  'Lists any taxonomic or life stage restrictions for a sample attribute usage within a survey dataset. For '
  'example an attribute might only be enabled for moth records or for plants in flower. If no restrictions are listed '
  'for an attribute usage then it applies to all records in the dataset.';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.sample_attributes_website_id IS
  'Foreign key to the sample_attributes_websites table. Identifies the attribute usage scenario this restriction '
  'applies to';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the '
  'taxon.';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.restrict_to_stage_term_meaning_id IS
  'Foreign key to the meanings table. For attributes that are only applicable to a given life stage, identifies the '
  'stage.';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN sample_attribute_taxon_restrictions.deleted IS 'Has this list been deleted?';

CREATE TABLE taxa_taxon_list_attribute_taxon_restrictions
(
  id serial NOT NULL,
  taxon_lists_taxa_taxon_list_attribute_id integer,
  restrict_to_taxon_meaning_id integer,
  restrict_to_stage_term_meaning_id integer,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_taxa_taxon_list_attribute_taxon_restrictions PRIMARY KEY (id),
  CONSTRAINT fk_taxa_taxon_list_attribute_taxon_restrictions_taxon_lists_taxa_taxon_list_attribute FOREIGN KEY (taxon_lists_taxa_taxon_list_attribute_id)
      REFERENCES taxon_lists_taxa_taxon_list_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxa_taxon_list_attribute_taxon_restrictions_taxon_meaning FOREIGN KEY (restrict_to_taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxa_taxon_list_attribute_taxon_restrictions_termlists_term FOREIGN KEY (restrict_to_stage_term_meaning_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT occurrence_attribute_taxon_restrictions_updater FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE taxa_taxon_list_attribute_taxon_restrictions IS
  'Lists any taxonomic or life stage restrictions for a taxon attribute usage within a taxon list dataset. For '
  'example an attribute might only be enabled for moth records or for plants in flower. If no restrictions are listed '
  'for an attribute usage then it applies to all taxa in the list.';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.taxon_lists_taxa_taxon_list_attribute_id IS
  'Foreign key to the taxon_lists_taxa_taxon_list_attributes table. Identifies the attribute usage scenario this '
  'restriction applies to';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.restrict_to_taxon_meaning_id IS
  'Foreign key to the taxon_meanings table. For attributes that are only applicable to a given taxon, identifies the '
  'taxon.';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.restrict_to_stage_term_meaning_id IS
  'Foreign key to the meanings table. For attributes that are only applicable to a given life stage, identifies the '
  'stage.';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxa_taxon_list_attribute_taxon_restrictions.deleted IS 'Has this list been deleted?';