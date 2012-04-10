-- Table: subject_observation_attributes_websites

-- DROP TABLE subject_observation_attributes_websites;

CREATE TABLE subject_observation_attributes_websites
(
  id serial NOT NULL,
  website_id integer NOT NULL, -- Foreign key to the websites table. Identifies the website that the subject_observation attribute is available for.
  subject_observation_attribute_id integer NOT NULL, -- Foreign key to the subject_observation_attributes table. Identifies the subject_observation attribute that is available for the website.
  restrict_to_survey_id integer, -- Foreign key to the survey table. For attributes that are only applicable to a given survey, identifies the survey.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  form_structure_block_id integer, -- Additional validation rules that are defined for this attribute but only active within the context of this survey/website.
  validation_rules character varying(500),
  weight integer NOT NULL DEFAULT 0, -- Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.
  control_type_id integer, -- Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this website on a dynamically generated form.
  default_text_value text, -- For default text values, provides the value.
  default_float_value double precision, -- For default float values, provides the value.
  default_int_value integer, -- For default integer values, provides the value. For default lookup values, provides the term id.
  default_date_start_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_end_value date, -- For default vague date and date values, provides the start date of the range of dates covered by the date.
  default_date_type_value character varying(2), -- For default vague date values, provides the date type identifier.
  CONSTRAINT pk_subject_observation_attributes_websites PRIMARY KEY (id),
  CONSTRAINT fk_subject_observation_attributes_website_control_type FOREIGN KEY (control_type_id)
      REFERENCES control_types (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_attributes_website_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_attributes_website_form_structure_block FOREIGN KEY (form_structure_block_id)
      REFERENCES form_structure_blocks (id) MATCH SIMPLE
      ON UPDATE SET NULL ON DELETE SET NULL,
  CONSTRAINT fk_subject_observation_attributes_websites_subject_observation_attributes FOREIGN KEY (subject_observation_attribute_id)
      REFERENCES subject_observation_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_attributes_websites_survey FOREIGN KEY (restrict_to_survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_attributes_websites_websites FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE subject_observation_attributes_websites IS 'Join table which identifies the subject_observation attributes that are available when entering subject_observation data on each website.';
COMMENT ON COLUMN subject_observation_attributes_websites.website_id IS 'Foreign key to the websites table. Identifies the website that the subject_observation attribute is available for.';
COMMENT ON COLUMN subject_observation_attributes_websites.subject_observation_attribute_id IS 'Foreign key to the subject_observation_attributes table. Identifies the subject_observation attribute that is available for the website.';
COMMENT ON COLUMN subject_observation_attributes_websites.restrict_to_survey_id IS 'restrict_to_survey_id integer, -- Foreign key to the survey table. For attributes that are only applicable to a given survey, identifies the survey.';
COMMENT ON COLUMN subject_observation_attributes_websites.created_on IS 'Date this record was created.';
COMMENT ON COLUMN subject_observation_attributes_websites.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN subject_observation_attributes_websites.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN subject_observation_attributes_websites.form_structure_block_id IS 'Additional validation rules that are defined for this attribute but only active within the context of this survey/website.';
COMMENT ON COLUMN subject_observation_attributes_websites.weight IS 'Dictates the order of controls within the block or at the top level. Controls with a higher weight will sink to the end of the list.';
COMMENT ON COLUMN subject_observation_attributes_websites.control_type_id IS 'Foreign key to the control_types table. Identifies the default type of control used for this attribute when used in this survey on a dynamically generated form.';
COMMENT ON COLUMN subject_observation_attributes_websites.default_text_value IS 'For default text values, provides the value.';
COMMENT ON COLUMN subject_observation_attributes_websites.default_float_value IS 'For default float values, provides the value.';
COMMENT ON COLUMN subject_observation_attributes_websites.default_int_value IS 'For default integer values, provides the value. For default lookup values, provides the term id. ';
COMMENT ON COLUMN subject_observation_attributes_websites.default_date_start_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN subject_observation_attributes_websites.default_date_end_value IS 'For default vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN subject_observation_attributes_websites.default_date_type_value IS 'For default vague date values, provides the date type identifier.';

-- Index: fki_subject_observation_attributes_websites_survey

-- DROP INDEX fki_subject_observation_attributes_websites_survey;

CREATE INDEX fki_subject_observation_attributes_websites_survey
  ON subject_observation_attributes_websites
  USING btree
  (restrict_to_survey_id);

-- Index: fki_subject_observation_attributes_website

-- DROP INDEX fki_subject_observation_attributes_website;

CREATE INDEX fki_subject_observation_attributes_website
  ON subject_observation_attributes_websites
  USING btree
  (website_id);
