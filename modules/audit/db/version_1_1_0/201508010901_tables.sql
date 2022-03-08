
--- Conceiveable that a location can be allocated to more than one website, and in extremo, samples and occurrences could
--- have their websites changed.

CREATE TABLE audit.logged_actions (
    id bigserial PRIMARY KEY,
    transaction_id BIGINT,
    action_tstamp_tx TIMESTAMP WITH TIME ZONE NOT NULL,
    schema_name text NOT NULL,
    event_table_name text NOT NULL,
    action CHAR(1) NOT NULL CHECK (action IN ('I','D','U', 'T')),
    statement_only BOOLEAN NOT NULL,

    event_record_id BIGINT,
    search_table_name text NOT NULL,
    search_key BIGINT,
    session_user_name text,
    updated_by_id BIGINT,
    
    row_data hstore,
    changed_fields hstore
);
 
REVOKE ALL ON audit.logged_actions FROM public;
 
COMMENT ON TABLE audit.logged_actions IS 'History of auditable actions on audited tables, from audit.if_modified_func()';
COMMENT ON COLUMN audit.logged_actions.id IS 'Unique identifier for each auditable event';
COMMENT ON COLUMN audit.logged_actions.schema_name IS 'Database schema audited table for this event is in';
COMMENT ON COLUMN audit.logged_actions.event_table_name IS 'Non-schema-qualified table name of table event occured in';
COMMENT ON COLUMN audit.logged_actions.session_user_name IS 'Login / session user whose statement caused the audited event';
COMMENT ON COLUMN audit.logged_actions.action_tstamp_tx IS 'Transaction start timestamp for tx in which audited event occurred';
COMMENT ON COLUMN audit.logged_actions.transaction_id IS 'Identifier of transaction that made the change. May wrap, but unique paired with action_tstamp_tx.';
COMMENT ON COLUMN audit.logged_actions.action IS 'Action type; I = insert, D = delete, U = update, T = truncate';
COMMENT ON COLUMN audit.logged_actions.event_record_id IS 'The Indicia ID of the record on which the event occurred';
COMMENT ON COLUMN audit.logged_actions.updated_by_id IS 'The Indicia ID of the user who updated the record';
COMMENT ON COLUMN audit.logged_actions.row_data IS 'Record value. Null for statement-level trigger. For INSERT this is the new tuple. For DELETE and UPDATE it is the old tuple.';
COMMENT ON COLUMN audit.logged_actions.changed_fields IS 'New values of fields changed by UPDATE. Null except for row-level UPDATE events.';
COMMENT ON COLUMN audit.logged_actions.statement_only IS '''t'' if audit event is from an FOR EACH STATEMENT trigger, ''f'' for FOR EACH ROW';
COMMENT ON COLUMN audit.logged_actions.search_table_name IS 'The table name to search on: allows subtables to be bundled with main tables';
COMMENT ON COLUMN audit.logged_actions.search_key IS 'The table key to search on: allows subtables to be bundled with main tables';
 
CREATE INDEX logged_actions_action_tstamp_tx_idx ON audit.logged_actions(action_tstamp_tx);
CREATE INDEX logged_actions_action_idx ON audit.logged_actions(action);

--- each indicia location can be assigned to > 1 indicia website, so need 
--- extra table to allow for easy search within Indicia (which doesn't handle 
--- postgres array data types)
CREATE TABLE audit.logged_actions_websites (
    logged_action_id BIGINT,
    website_id BIGINT
);
 
REVOKE ALL ON audit.logged_actions_websites FROM public;
 
COMMENT ON TABLE audit.logged_actions_websites IS 'Mapping of auditable events to Indicia Websites, from audit.if_modified_func()';
COMMENT ON COLUMN audit.logged_actions_websites.logged_action_id IS 'Identifier for each auditable event';
COMMENT ON COLUMN audit.logged_actions_websites.website_id IS 'The Indicia ID of a website that the event is associated with.';
 
CREATE INDEX logged_actions_websites_logged_action_idx ON audit.logged_actions_websites(logged_action_id);
CREATE INDEX logged_actions_websites_website_idx ON audit.logged_actions_websites(website_id);

