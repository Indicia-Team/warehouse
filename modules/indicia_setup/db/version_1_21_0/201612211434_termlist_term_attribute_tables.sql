-- Table: termlists_term_attributes

-- DROP TABLE termlists_term_attributes;

CREATE TABLE termlists_term_attributes
(
  id serial NOT NULL,
  caption character varying(50), -- Display caption for the attribute.
  description character varying, -- Description of the attribute.
  data_type character(1), -- Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  validation_rules character varying, -- Validation rules defined for this attribute, for example: number, required,max[50].
  termlist_id integer, -- For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.
  multi_value boolean DEFAULT false, -- Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.
  public boolean NOT NULL DEFAULT false, -- Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  system_function character varying(30), -- Machine readable function of this attribute. Defines how the field can be interpreted by the system.
  CONSTRAINT pk_termlists_term_attributes PRIMARY KEY (id),
  CONSTRAINT fk_termlists_term_attribute_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_term_attribute_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_term_attributes_termlists FOREIGN KEY (termlist_id)
      REFERENCES termlists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE termlists_term_attributes
  IS 'List of additional attributes that are defined for termlists_term dataset records.';
COMMENT ON COLUMN termlists_term_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN termlists_term_attributes.description IS 'Description of the attribute.';
COMMENT ON COLUMN termlists_term_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN termlists_term_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists_term_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists_term_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists_term_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN termlists_term_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN termlists_term_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN termlists_term_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN termlists_term_attributes.public IS 'Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.';
COMMENT ON COLUMN termlists_term_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN termlists_term_attributes.system_function IS 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';

-- Table: termlists_term_attribute_values

-- DROP TABLE termlists_term_attribute_values;

CREATE TABLE termlists_term_attribute_values
(
  id serial NOT NULL,
  termlists_term_id integer, -- Foreign key to the termlists_terms table. Identifies the termlists_term that this value applies to.
  termlists_term_attribute_id integer, -- Foreign key to the termlists_term_attributes table. Identifies the attribute that this value is for.
  text_value text, -- For text values, provides the value.
  float_value double precision, -- For float values, provides the value.
  int_value integer, -- For integer values, provides the value. For lookup values, provides the term id.
  date_start_value date, -- For vague date and date values, provides the start date of the range of dates covered by the date.
  date_end_value date, -- For vague date and date values, provides the start date of the range of dates covered by the date.
  date_type_value character varying(2), -- For vague date values, provides the date type identifier.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_termlists_term_attribute_values PRIMARY KEY (id),
  CONSTRAINT fk_termlists_term_attribute_value_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_term_attribute_value_termlists_term_attribute FOREIGN KEY (termlists_term_attribute_id)
      REFERENCES termlists_term_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_term_attribute_value_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_term_attribute_values_termlists_term FOREIGN KEY (termlists_term_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE termlists_term_attribute_values
  IS 'Contains values that have been stored for termlists_term datasets against custom attributes.';
COMMENT ON COLUMN termlists_term_attribute_values.termlists_term_id IS 'Foreign key to the termlists_terms table. Identifies the termlists_term that this value applies to.';
COMMENT ON COLUMN termlists_term_attribute_values.termlists_term_attribute_id IS 'Foreign key to the termlists_term_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN termlists_term_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN termlists_term_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN termlists_term_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN termlists_term_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN termlists_term_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN termlists_term_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN termlists_term_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists_term_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists_term_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN termlists_term_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN termlists_term_attribute_values.deleted IS 'Has this record been deleted?';


-- Index: fki_termlists_term_attribute_value_termlists_term_attribute

-- DROP INDEX fki_termlists_term_attribute_value_termlists_term_attribute;

CREATE INDEX fki_termlists_term_attribute_value_termlists_term_attribute
  ON termlists_term_attribute_values
  USING btree
  (termlists_term_attribute_id);

-- Index: fki_termlists_term_attribute_values_termlists_term

-- DROP INDEX fki_termlists_term_attribute_values_termlists_term;

CREATE INDEX fki_termlists_term_attribute_values_termlists_term
  ON termlists_term_attribute_values
  USING btree
  (termlists_term_id);

-- Table: termlists_termlists_term_attributes

-- DROP TABLE termlists_termlists_term_attributes;

CREATE TABLE termlists_termlists_term_attributes
(
  id serial NOT NULL,
  termlist_id integer NOT NULL, -- Foreign key to the termlists table. Identifies the terlmist that the termlist_term attribute is available for.
  termlists_term_attribute_id integer NOT NULL, -- Foreign key to the termlists_term_attributes table. Identifies the termlists_term attribute that is available for the website.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  form_structure_block_id integer,
  validation_rules character varying(500), -- Additional validation rules that are defined for this attribute but only active within the context of this termlist.
  weight integer NOT NULL DEFAULT 0, -- Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.
  control_type_id integer, -- Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this termlists_term on a dynamically generated form.
  default_text_value text, -- For default text values, provides the value.
  default_float_value double precision, -- For default float values, provides the value.
  default_int_value integer, -- For default integer values, provides the value. For default lookup values, provides the term id.
  default_date_start_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_end_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_type_value character varying(2), -- For default vague date values, provides the date type identifier.
  CONSTRAINT pk_termlists_termlists_term_attributes PRIMARY KEY (id),
  CONSTRAINT fk_termlists_termlists_term_attributes_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_termlists_term_attributes_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_termlists_term_attributes_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL,
  CONSTRAINT fk_termlists_termlists_term_attributes_termlists_term_attributes FOREIGN KEY (termlists_term_attribute_id)
      REFERENCES termlists_term_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_termlists_termlists_term_attributes_termlists FOREIGN KEY (termlist_id)
      REFERENCES termlists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE termlists_termlists_term_attributes
  IS 'Join table which identifies the termlists that each termlists_term attribute is available for.';
COMMENT ON COLUMN termlists_termlists_term_attributes.termlist_id IS 'Foreign key to the termlists table. Identifies the termlist that the termlists_term attribute is available for.';
COMMENT ON COLUMN termlists_termlists_term_attributes.termlists_term_attribute_id IS 'Foreign key to the termlists_term_attributes table. Identifies the termlists_term attribute that is available for the website.';
COMMENT ON COLUMN termlists_termlists_term_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN termlists_termlists_term_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN termlists_termlists_term_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN termlists_termlists_term_attributes.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this termlist.';
COMMENT ON COLUMN termlists_termlists_term_attributes.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN termlists_termlists_term_attributes.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this termlist on a dynamically generated form.';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN termlists_termlists_term_attributes.default_date_type_value IS 'For default vague date values, provides the date type identifier.';