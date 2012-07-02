-- Table: identifiers_subject_observation_attributes

-- DROP TABLE identifiers_subject_observation_attributes;

CREATE TABLE identifiers_subject_observation_attributes
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
  public boolean DEFAULT false, -- Flag set to true if this attribute is available for all identifiers_subject_observations on the warehouse or false if only available when the identifiers_subject_observation is a user of a website linked to the attribute.
  system_function character varying(30), -- Machine readable function of this attribute. Defines how the field can be interpreted by the system.
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_identifiers_subject_observation_attributes PRIMARY KEY (id),
  CONSTRAINT fk_identifiers_subject_observation_attribute_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observation_attribute_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observation_attributes_termlists FOREIGN KEY (termlist_id)
      REFERENCES termlists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE identifiers_subject_observation_attributes IS 'List of additional attributes that are defined for the identifiers_subject_observations data.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.caption IS 'Display caption for the attribute.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.data_type IS 'Data type for the attribute. Possible values are T (text), I (integer), F (float), D (date), V (vague date), L (item looked up from a termlist).';
COMMENT ON COLUMN identifiers_subject_observation_attributes.created_on IS 'Date this record was created.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN identifiers_subject_observation_attributes.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN identifiers_subject_observation_attributes.validation_rules IS 'Validation rules defined for this attribute, for example: number, required,max[50].';
COMMENT ON COLUMN identifiers_subject_observation_attributes.termlist_id IS 'For attributes which define a term from a termlist, provides the ID of the termlist the term can be selected from.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.multi_value IS 'Does this attribute allow multiple values? If set to true, then multiple values for this attribute can be stored against a single record.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.public IS 'Flag set to true if this attribute is available for all identifiers_subject_observations on the warehouse or false if only available when the identifiers_subject_observation is a user of a website linked to the attribute.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.system_function IS 'Machine readable function of this attribute. Defines how the field can be interpreted by the system.';
COMMENT ON COLUMN identifiers_subject_observation_attributes.deleted IS 'Has this record been deleted?';
