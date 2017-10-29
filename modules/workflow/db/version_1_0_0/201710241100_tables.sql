

-- Sequences
CREATE SEQUENCE workflow_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE SEQUENCE workflow_undo_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;

CREATE SEQUENCE workflow_metadata_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


-- Tables
CREATE TABLE workflow_events
(
  id                      integer           NOT NULL DEFAULT nextval('workflow_events_id_seq'::regclass),
  entity                  character varying NOT NULL,
  event_type              character(1)      NOT NULL, --- not constrained due to possible future expansion
  key                     character varying NOT NULL,
  key_value               character varying NOT NULL,
  mimic_rewind_first      boolean           DEFAULT false NOT NULL,
  values                  character varying NOT NULL,
  created_on              timestamp without time zone NOT NULL,
  created_by_id           integer           NOT NULL,
  updated_on              timestamp without time zone NOT NULL,
  updated_by_id           integer           NOT NULL,
  deleted                 boolean           DEFAULT false NOT NULL,
  
  CONSTRAINT pk_workflow_events PRIMARY KEY (id),
  CONSTRAINT fk_workflow_events_creator FOREIGN KEY (created_by_id) REFERENCES users(id),
  CONSTRAINT fk_workflow_events_updater FOREIGN KEY (updated_by_id) REFERENCES users(id)
)
WITH (
  OIDS=FALSE
);

CREATE UNIQUE INDEX ix_unique_workflow_event ON workflow_events (entity, event_type, key, key_value);

COMMENT ON TABLE workflow_events IS 'Definition of events that trigger an action by the Workflow module';
COMMENT ON COLUMN workflow_events.id IS 'Unique identifier for each workflow event';
COMMENT ON COLUMN workflow_events.entity IS 'The database entity/table against which the event is registered.';
COMMENT ON COLUMN workflow_events.event_type IS 'Event type; C = record creation, U = record update, V = Verification, R = Rejection';
COMMENT ON COLUMN workflow_events.key IS 'The column in the entity which is used to identify which records trigger this event';
COMMENT ON COLUMN workflow_events.key_value IS 'The value in the key column which causes this event to trigger';
COMMENT ON COLUMN workflow_events.values IS 'JSON encoded array of column/valiue pairs to be set when this event is triggered.';
COMMENT ON COLUMN workflow_events.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN workflow_events.created_on IS 'Date this record was created.';
COMMENT ON COLUMN workflow_events.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN workflow_events.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN workflow_events.updated_by_id IS 'Foreign key to the users table (last updater).';


CREATE TABLE workflow_undo (
  id                      integer           NOT NULL DEFAULT nextval('workflow_undo_id_seq'::regclass),
  entity                  character varying NOT NULL,
  entity_id               integer, --- actual FK depends on entity
  event_type              character(1)      NOT NULL,
  original_values         character varying,
  active                  boolean           DEFAULT true NOT NULL,
  created_on              timestamp without time zone NOT NULL,
  created_by_id           integer           NOT NULL,

  CONSTRAINT pk_workflow_undo PRIMARY KEY (id),
  CONSTRAINT fk_workflow_undo_creator FOREIGN KEY (created_by_id) REFERENCES users(id)
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE workflow_undo IS 'Definition of events that trigger an action by the Workflow module';
COMMENT ON COLUMN workflow_undo.id IS 'Unique identifier for each workflow event';
--- TODO CREATE OTHER COLUMN COMMENTS
COMMENT ON COLUMN workflow_undo.created_on IS 'Date this record was created.';
COMMENT ON COLUMN workflow_undo.created_by_id IS 'Foreign key to the users table (creator).';


CREATE TABLE workflow_metadata
(
  id                               integer           NOT NULL DEFAULT nextval('workflow_metadata_id_seq'::regclass),
  entity                           character varying NOT NULL,
  key                              character varying NOT NULL,
  key_value                        character varying NOT NULL,
  verification_delay_hours         integer           DEFAULT 0 NOT NULL,
  verifier_notifications_immediate boolean           DEFAULT false NOT NULL,
  log_all_communications           boolean           DEFAULT true NOT NULL,
  created_on                       timestamp without time zone NOT NULL,
  created_by_id                    integer           NOT NULL,
  updated_on                       timestamp without time zone NOT NULL,
  updated_by_id                    integer           NOT NULL,
  deleted                          boolean           DEFAULT false NOT NULL,
  
  CONSTRAINT pk_workflow_metadata PRIMARY KEY (id),
  CONSTRAINT fk_workflow_metadata_creator FOREIGN KEY (created_by_id) REFERENCES users(id),
  CONSTRAINT fk_workflow_metadata_updater FOREIGN KEY (updated_by_id) REFERENCES users(id)
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE workflow_metadata IS 'Definition of events that trigger an action by the Workflow module';
COMMENT ON COLUMN workflow_metadata.id IS 'Unique identifier for each workflow event';
COMMENT ON COLUMN workflow_metadata.entity IS 'The database entity/table against which the event is registered.';
COMMENT ON COLUMN workflow_metadata.key IS 'The column in the entity which is used to identify which records trigger this event';
COMMENT ON COLUMN workflow_metadata.key_value IS 'The value in the key column which causes this event to trigger';
--- TODO CREATE OTHER COLUMN COMMENTS
COMMENT ON COLUMN workflow_metadata.created_on IS 'Date this record was created.';
COMMENT ON COLUMN workflow_metadata.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN workflow_metadata.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN workflow_metadata.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN workflow_metadata.deleted IS 'Has this record been deleted?';


-- Views
--- TODO CREATE COLUMN COMMENTS FOR VIEWS
CREATE VIEW gv_workflow_events AS
 SELECT we.id, we.entity, we.event_type, we.key, we.key_value
   FROM workflow_events we
  WHERE we.deleted = false;

CREATE VIEW gv_workflow_undo AS
 SELECT wu.id, wu.event_type
   FROM workflow_undo wu;

CREATE VIEW gv_workflow_metadata AS
 SELECT wm.id, wm.entity, wm.key, wm.key_value
   FROM workflow_metadata wm
  WHERE wm.deleted = false;


