CREATE TABLE occurrence_attributes_taxa_taxon_list_attributes
(
  id serial NOT NULL,
  occurrence_attribute_id integer NOT NULL,
  taxa_taxon_list_attribute_id integer NOT NULL,
  restrict_occurrence_attribute_to_single_value boolean,
  validate_occurrence_attribute_values_against_taxon_values boolean NOT NULL default false,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_occurrence_attributes_taxa_taxon_list_attributes PRIMARY KEY (id),
  CONSTRAINT fk_occurrence_attributes_taxa_taxon_list_attributes_occurrence_attribute FOREIGN KEY (occurrence_attribute_id)
      REFERENCES occurrence_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_attributes_taxa_taxon_list_attributes_taxa_taxon_list_attribute FOREIGN KEY (taxa_taxon_list_attribute_id)
      REFERENCES taxa_taxon_list_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_attributes_taxa_taxon_list_attributes_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_attributes_taxa_taxon_list_attributes_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE occurrence_attributes_taxa_taxon_list_attributes IS
  'Table linking attributes that capture information about a taxon to the matching attribute that captures information about instances of a taxon.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.occurrence_attribute_id IS
  'Foreign key to the occurrence attributes table. Linked occurrence attribute.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.taxa_taxon_list_attribute_id IS
  'Foreign key to the taxa_taxon_list_attributes table. Linked taxon attribute.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.restrict_occurrence_attribute_to_single_value IS
  'If true, then a multi-value or range taxon attribute will be mapped to a single value occurrence attribute.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.validate_occurrence_attribute_values_against_taxon_values IS
  'If true, then occurrence attribute values for the linked attribute should be validated to ensure that they fall within the range or list of '
  'values provided for the taxon attribute values.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN occurrence_attributes_taxa_taxon_list_attributes.deleted IS 'Has this record been deleted?';

CREATE UNIQUE INDEX ix_unique_occurrence_attributes_taxa_taxon_list_attributes
  ON occurrence_attributes_taxa_taxon_list_attributes (occurrence_attribute_id, taxa_taxon_list_attribute_id)
  WHERE (deleted=false);

CREATE TABLE sample_attributes_taxa_taxon_list_attributes
(
  id serial NOT NULL,
  sample_attribute_id integer NOT NULL,
  taxa_taxon_list_attribute_id integer NOT NULL,
  restrict_sample_attribute_to_single_value boolean,
  validate_sample_attribute_values_against_taxon_values boolean NOT NULL default false,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_sample_attributes_taxa_taxon_list_attributes PRIMARY KEY (id),
  CONSTRAINT fk_sample_attributes_taxa_taxon_list_attributes_sample_attribute FOREIGN KEY (sample_attribute_id)
      REFERENCES sample_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_attributes_taxa_taxon_list_attributes_taxa_taxon_list_attribute FOREIGN KEY (taxa_taxon_list_attribute_id)
      REFERENCES taxa_taxon_list_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_attributes_taxa_taxon_list_attributes_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_attributes_taxa_taxon_list_attributes_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE sample_attributes_taxa_taxon_list_attributes IS
  'Table linking attributes that capture information about a taxon to the matching attribute that captures information about instances of a taxon.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.sample_attribute_id IS
  'Foreign key to the sample attributes table. Linked sample attribute.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.taxa_taxon_list_attribute_id IS
  'Foreign key to the taxa_taxon_list_attributes table. Linked taxon attribute.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.restrict_sample_attribute_to_single_value IS
  'If true, then a multi-value or range taxon attribute will be mapped to a single value sample attribute.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.validate_sample_attribute_values_against_taxon_values IS
  'If true, then sample attribute values for the linked attribute should be validated to ensure that they fall within the range or list of '
  'values provided for the taxon attribute values.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN sample_attributes_taxa_taxon_list_attributes.deleted IS 'Has this record been deleted?';

CREATE UNIQUE INDEX ix_unique_sample_attributes_taxa_taxon_list_attributes
  ON sample_attributes_taxa_taxon_list_attributes (sample_attribute_id, taxa_taxon_list_attribute_id)
  WHERE (deleted=false);