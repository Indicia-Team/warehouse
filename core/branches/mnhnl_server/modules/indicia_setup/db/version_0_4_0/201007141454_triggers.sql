-- Table: triggers

-- DROP TABLE triggers;

CREATE TABLE triggers
(
  id serial NOT NULL,
  "name" character varying(100) NOT NULL, -- Name of the trigger.
  description text, -- Description of the trigger.
  public boolean NOT NULL DEFAULT false, -- Description of the trigger.
  enabled boolean NOT NULL DEFAULT true, -- Is the trigger enabled at this point in time?
  deleted boolean NOT NULL DEFAULT false, -- Set to true if the record is mark deleted.
  trigger_template_file character varying(500), -- Defines the name of the xml file in the triggers folder which contains the query that will be run for this query.
  params_json character varying NOT NULL, -- Contains a JSON format structure the parameters to be sent to the query.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  CONSTRAINT pk_triggers PRIMARY KEY (id),
  CONSTRAINT fk_trigger_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_trigger_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON COLUMN triggers."name" IS 'Name of the trigger.';
COMMENT ON COLUMN triggers.description IS 'Description of the trigger.';
COMMENT ON COLUMN triggers.public IS 'Description of the trigger.';
COMMENT ON COLUMN triggers.enabled IS 'Is the trigger enabled at this point in time?';
COMMENT ON COLUMN triggers.deleted IS 'Set to true if the record is mark deleted.';
COMMENT ON COLUMN triggers.params_json IS 'Contains a JSON format structure the parameters to be sent to the query.';
COMMENT ON COLUMN triggers.created_on IS 'Date this record was created.';
COMMENT ON COLUMN triggers.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN triggers.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN triggers.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN triggers.trigger_template_file IS 'Defines the name of the xml file in the triggers folder which contains the query that will be run for this query.';