CREATE TABLE trigger_actions
(
  id serial NOT NULL,
  trigger_id integer NOT NULL,
  "type" character(1) NOT NULL,
  param1 character varying,
  param2 character varying,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_trigger_actions PRIMARY KEY (id),
  CONSTRAINT fk_trigger_action_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_trigger_action_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_trigger_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

ALTER TABLE trigger_actions
  ADD CONSTRAINT chk_trigger_action_type CHECK (type = ANY (ARRAY['E'::"char", 'U'::"char"]));
  
COMMENT ON COLUMN trigger_actions.trigger_id IS 'Foreign key to the triggers table. Identifies the trigger that this action is taken for.';
COMMENT ON COLUMN trigger_actions."type" IS 'Type of action taken. Currently supports E (email sent) or U (url accessed).';
COMMENT ON COLUMN trigger_actions."param1" IS 'First parameter for the action. Either the user ID for emails, or the URL.';
COMMENT ON COLUMN trigger_actions."param2" IS 
    'Second parameter for the action if required. For email actions, specifies the timing of the email, which can be I (immediate), D (daily), W (weekly) or null (use the default setting for this user)';
COMMENT ON COLUMN trigger_actions.created_on IS 'Date this record was created.';
COMMENT ON COLUMN trigger_actions.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN trigger_actions.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN trigger_actions.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN trigger_actions.deleted IS 'Has this record been deleted?';

