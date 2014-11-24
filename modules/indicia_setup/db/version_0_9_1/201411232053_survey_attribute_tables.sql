-- Table: survey_attributes

-- DROP TABLE survey_attributes;

CREATE TABLE survey_attributes
(
  id serial NOT NULL,
  caption character varying(50), -- Display caption for the attribute.
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
  CONSTRAINT pk_survey_attributes PRIMARY KEY (id),
  CONSTRAINT fk_survey_attribute_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attribute_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attributes_termlists FOREIGN KEY (termlist_id)
      REFERENCES termlists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE survey_attributes
  IS 'List of additional attributes that are defined for survey dataset records.';
COMMENT ON COLUMN survey_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN survey_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN survey_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN survey_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN survey_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN survey_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN survey_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN survey_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN survey_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN survey_attributes.public IS 'Flag set to true if this attribute is available for selection and use by any website. If false the attribute is only available for use in the website which created it.';
COMMENT ON COLUMN survey_attributes.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN survey_attributes.system_function IS 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';

-- Table: survey_attribute_values

-- DROP TABLE survey_attribute_values;

CREATE TABLE survey_attribute_values
(
  id serial NOT NULL,
  survey_id integer, -- Foreign key to the surveys table. Identifies the survey that this value applies to.
  survey_attribute_id integer, -- Foreign key to the survey_attributes table. Identifies the attribute that this value is for.
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
  CONSTRAINT pk_survey_attribute_values PRIMARY KEY (id),
  CONSTRAINT fk_survey_attribute_value_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attribute_value_survey_attribute FOREIGN KEY (survey_attribute_id)
      REFERENCES survey_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attribute_value_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attribute_values_survey FOREIGN KEY (survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE survey_attribute_values
  IS 'Contains values that have been stored for survey datasets against custom attributes.';
COMMENT ON COLUMN survey_attribute_values.survey_id IS 'Foreign key to the surveys table. Identifies the survey that this value applies to.';
COMMENT ON COLUMN survey_attribute_values.survey_attribute_id IS 'Foreign key to the survey_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN survey_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN survey_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN survey_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN survey_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN survey_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN survey_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN survey_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN survey_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN survey_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN survey_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN survey_attribute_values.deleted IS 'Has this record been deleted?';


-- Index: fki_survey_attribute_value_survey_attribute

-- DROP INDEX fki_survey_attribute_value_survey_attribute;

CREATE INDEX fki_survey_attribute_value_survey_attribute
  ON survey_attribute_values
  USING btree
  (survey_attribute_id);

-- Index: fki_survey_attribute_values_survey

-- DROP INDEX fki_survey_attribute_values_survey;

CREATE INDEX fki_survey_attribute_values_survey
  ON survey_attribute_values
  USING btree
  (survey_id);

-- Table: survey_attributes_websites

-- DROP TABLE survey_attributes_websites;

CREATE TABLE survey_attributes_websites
(
  id serial NOT NULL,
  website_id integer NOT NULL, -- Foreign key to the websites table. Identifies the website that the survey attribute is available for.
  survey_attribute_id integer NOT NULL, -- Foreign key to the survey_attributes table. Identifies the survey attribute that is available for the website.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  form_structure_block_id integer, -- Additional validation rules that are defined for this attribute but only active within the context of this survey/website.
  validation_rules character varying(500),
  weight integer NOT NULL DEFAULT 0, -- Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.
  control_type_id integer, -- Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.
  default_text_value text, -- For default text values, provides the value.
  default_float_value double precision, -- For default float values, provides the value.
  default_int_value integer, -- For default integer values, provides the value. For default lookup values, provides the term id.
  default_date_start_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_end_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_type_value character varying(2), -- For default vague date values, provides the date type identifier.
  CONSTRAINT pk_survey_attributes_websites PRIMARY KEY (id),
  CONSTRAINT fk_survey_attributes_website_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attributes_website_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL,
  CONSTRAINT fk_survey_attributes_websites_survey_attributes FOREIGN KEY (survey_attribute_id)
      REFERENCES survey_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_attributes_websites_websites FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE survey_attributes_websites
  IS 'Join table which identifies the websites that each survey attribute is available for.';
COMMENT ON COLUMN survey_attributes_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the survey attribute is available for.';
COMMENT ON COLUMN survey_attributes_websites.survey_attribute_id IS 'Foreign key to the survey_attributes table. Identifies the survey attribute that is available for the website.';
COMMENT ON COLUMN survey_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN survey_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN survey_attributes_websites.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN survey_attributes_websites.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this survey/website.';
COMMENT ON COLUMN survey_attributes_websites.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN survey_attributes_websites.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.';
COMMENT ON COLUMN survey_attributes_websites.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN survey_attributes_websites.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN survey_attributes_websites.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN survey_attributes_websites.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN survey_attributes_websites.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN survey_attributes_websites.default_date_type_value IS 'For default vague date values, provides the date type identifier.';


