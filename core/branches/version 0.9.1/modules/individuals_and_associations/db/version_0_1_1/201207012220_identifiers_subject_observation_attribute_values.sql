-- Table: identifiers_subject_observation_attribute_values

-- DROP TABLE identifiers_subject_observation_attribute_values;

CREATE TABLE identifiers_subject_observation_attribute_values
(
  id serial NOT NULL,
  identifiers_subject_observation_id integer, -- Foreign key to the identifiers_subject_observations table. Identifies the identifiers_subject_observation that this value applies to.
  identifiers_subject_observation_attribute_id integer, -- Foreign key to the identifiers_subject_observation_attributes table. Identifies the attribute that this value is for.
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
  CONSTRAINT pk_identifiers_subject_observation_attribute_values PRIMARY KEY (id),
  CONSTRAINT fk_identifiers_subject_observation_attribute_value_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observation_attribute_value_identifiers_subject_observation_attribute FOREIGN KEY (identifiers_subject_observation_attribute_id)
      REFERENCES identifiers_subject_observation_attributes (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observation_attribute_value_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observation_attribute_values_identifiers_subject_observation FOREIGN KEY (identifiers_subject_observation_id)
      REFERENCES identifiers_subject_observations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE identifiers_subject_observation_attribute_values IS 'Contains values that have been stored for identifiers_subject_observations against custom attributes.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.identifiers_subject_observation_id IS 'Foreign key to the identifiers_subject_observations table. Identifies the identifiers_subject_observation that this value applies to.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.identifiers_subject_observation_attribute_id IS 'Foreign key to the identifiers_subject_observation_attributes table. Identifies the attribute that this value is for.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.text_value IS 'For text values, provides the value.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.float_value IS 'For float values, provides the value.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.int_value IS 'For integer values, provides the value. For lookup values, provides the term id. ';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.date_start_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.date_end_value IS 'For vague date and date values, provides the start date of the range of dates covered by the date.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.date_type_value IS 'For vague date values, provides the date type identifier.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.created_on IS 'Date this record was created.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN identifiers_subject_observation_attribute_values.deleted IS 'Has this record been deleted?';


-- Index: fki_identifiers_subject_observation_attribute_value_identifiers_subject_observation_attribute

-- DROP INDEX fki_identifiers_subject_observation_attribute_value_identifiers_subject_observation_attribute;

CREATE INDEX fki_identifiers_subject_observation_attribute_value_identifiers_subject_observation_attribute
  ON identifiers_subject_observation_attribute_values
  USING btree
  (identifiers_subject_observation_attribute_id);

-- Index: fki_identifiers_subject_observation_attribute_values_identifiers_subject_observation

-- DROP INDEX fki_identifiers_subject_observation_attribute_values_identifiers_subject_observation;

CREATE INDEX fki_identifiers_subject_observation_attribute_values_identifiers_subject_observation
  ON identifiers_subject_observation_attribute_values
  USING btree
  (identifiers_subject_observation_id);
