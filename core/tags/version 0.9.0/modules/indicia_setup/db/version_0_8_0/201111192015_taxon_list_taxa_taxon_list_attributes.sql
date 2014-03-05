
CREATE TABLE taxon_lists_taxa_taxon_list_attributes
(
  id serial NOT NULL,
  taxon_list_id integer NOT NULL, -- Foreign key to the taxon_lists table. Identifies the taxon_lists that the taxa_taxon_list attribute is available for.
  taxa_taxon_list_attribute_id integer NOT NULL, -- Foreign key to the taxa_taxon_list_attributes table. Identifies the taxa_taxon_list attribute that is available for the taxon list.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  form_structure_block_id integer, -- Additional validation rules that are defined for this attribute but only active within the context of this taxon list.
  validation_rules character varying(500),
  weight integer NOT NULL DEFAULT 0, -- Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.
  control_type_id integer, -- Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this taxon list.
  default_text_value text, -- For default text values, provides the value.
  default_float_value double precision, -- For default float values, provides the value.
  default_int_value integer, -- For default integer values, provides the value. For default lookup values, provides the term id.
  default_date_start_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_end_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_type_value character varying(2), -- For default vague date values, provides the date type identifier.
  CONSTRAINT pk_taxon_lists_taxa_taxon_list_attributes PRIMARY KEY (id),
  CONSTRAINT fk_taxon_lists_taxa_taxon_list_attribute_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_lists_taxa_taxon_list_attribute_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_lists_taxa_taxon_list_attribute_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL,
  CONSTRAINT fk_taxon_lists_taxa_taxon_list_attributes_taxa_taxon_list_attributes FOREIGN KEY (taxa_taxon_list_attribute_id)
      REFERENCES taxa_taxon_list_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_lists_taxa_taxon_list_attributes_taxon_lists FOREIGN KEY (taxon_list_id)
      REFERENCES taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE taxon_lists_taxa_taxon_list_attributes IS 'Join table which identifies the taxa_taxon_list attributes that are available when entering taxa_taxon_list data on each taxon list.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.taxon_list_id IS 'Foreign key to the taxon_lists table. Identifies the taxon_lists that the taxa_taxon_list attribute is available for.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.taxa_taxon_list_attribute_id IS 'Foreign key to the taxa_taxon_list_attributes table. Identifies the taxa_taxon_list attribute that is available for the taxon list.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this taxon list.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this taxon list on a dynamically generated form.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN taxon_lists_taxa_taxon_list_attributes.default_date_type_value IS 'For default vague date values, provides the date type identifier.';

