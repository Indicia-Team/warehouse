CREATE TABLE verification_rules
(
  id serial NOT NULL, -- Unique identifier for the verification_rule table. Primary key.
  title character varying NOT NULL, -- Title of the verification rule.
  description character varying, -- Description of the verification rule.
  test_type character varying NOT NULL, -- Name of the test rule type
  error_message character varying NOT NULL, -- Message to display when test fails
  source_url character varying, -- URL the verification rule was loaded from.
  source_filename character varying, -- Filename of the verification rule file it was loaded from.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  CONSTRAINT pk_verification_rule PRIMARY KEY (id),
  CONSTRAINT fk_user_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_verification_rule_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE verification_rules IS 'List of available verification rules.';
COMMENT ON COLUMN verification_rules.id IS 'Unique identifier for the verification_rule table. Primary key.';
COMMENT ON COLUMN verification_rules.title IS 'Title of the verification rule.';
COMMENT ON COLUMN verification_rules.description IS 'Description of the verification rule.';
COMMENT ON COLUMN verification_rules.test_type IS 'Name of the test rule type';
COMMENT ON COLUMN verification_rules.error_message IS 'Message to display when test fails';
COMMENT ON COLUMN verification_rules.source_url IS 'URL the verification rule was loaded from.';
COMMENT ON COLUMN verification_rules.source_filename IS 'Filename of the verification rule file it was loaded from.';
COMMENT ON COLUMN verification_rules.created_on IS 'Date this record was created.';
COMMENT ON COLUMN verification_rules.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN verification_rules.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN verification_rules.updated_by_id IS 'Foreign key to the users table (last updater).';


CREATE TABLE verification_rule_metadata
(
  id serial NOT NULL, -- Primary key and unique identifier for the verification_rule_metadata table.
  verification_rule_id integer NOT NULL, -- Foreign key to the verification rule this metadata item belongs to.
  "key" character varying NOT NULL, -- Name of the metadata item.
  "value" character varying NOT NULL, -- Metadata value.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  CONSTRAINT pk_verification_rule_metadata PRIMARY KEY (id),
  CONSTRAINT fk_verification_rule_metadata_verification_rule FOREIGN KEY (verification_rule_id)
      REFERENCES verification_rules (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE verification_rule_metadata IS 'Metadata for a verification rule';
COMMENT ON COLUMN verification_rule_metadata.id IS 'Primary key and unique identifier for the verification_rule_metadata table.';
COMMENT ON COLUMN verification_rule_metadata.verification_rule_id IS 'Foreign key to the verification rule this metadata item belongs to.';
COMMENT ON COLUMN verification_rule_metadata."key" IS 'Name of the metadata item.';
COMMENT ON COLUMN verification_rule_metadata."value" IS 'Metadata value.';
COMMENT ON COLUMN verification_rule_metadata.created_on IS 'Date this record was created.';
COMMENT ON COLUMN verification_rule_metadata.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN verification_rule_metadata.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN verification_rule_metadata.updated_by_id IS 'Foreign key to the users table (last updater).';

CREATE INDEX fki_verification_rule_metadata_verification_rule
  ON verification_rule_metadata
  USING btree
  (verification_rule_id);

CREATE TABLE verification_rule_data
(
  id serial NOT NULL, -- Primary key and unique identifier for the verification_rule_data table.
  verification_rule_id integer NOT NULL, -- Foreign key to the verification rule this data item belongs to.
  header_name character varying NOT NULL,
  data_group integer NULL,
  "key" character varying NOT NULL, -- Name of the data item.
  "value" character varying NOT NULL, -- Data value.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  CONSTRAINT pk_verification_rule_data PRIMARY KEY (id),
  CONSTRAINT fk_verification_rule_data_verification_rule FOREIGN KEY (verification_rule_id)
      REFERENCES verification_rules (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE verification_rule_data IS 'Data item for a verification rule';
COMMENT ON COLUMN verification_rule_data.id IS 'Primary key and unique identifier for the verification_rule_data table.';
COMMENT ON COLUMN verification_rule_data.verification_rule_id IS 'Foreign key to the verification rule this data item belongs to.';
COMMENT ON COLUMN verification_rule_data.header_name IS 'Header of the data section.';
COMMENT ON COLUMN verification_rule_data.data_group IS 'If a data section includes several groups of related data values, then identifies the group. E.g. if a period in year rule data includes several stage and start/end date pairs, this value groups them together.';
COMMENT ON COLUMN verification_rule_data."key" IS 'Name of the data item.';
COMMENT ON COLUMN verification_rule_data."value" IS 'Data value.';
COMMENT ON COLUMN verification_rule_data.created_on IS 'Date this record was created.';
COMMENT ON COLUMN verification_rule_data.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN verification_rule_data.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN verification_rule_data.updated_by_id IS 'Foreign key to the users table (last updater).';

CREATE INDEX fki_verification_rule_data_verification_rule
  ON verification_rule_data
  USING btree
  (verification_rule_id);

