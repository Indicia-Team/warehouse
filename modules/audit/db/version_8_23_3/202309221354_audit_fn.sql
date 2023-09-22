CREATE OR REPLACE FUNCTION audit.if_modified_func()
  RETURNS trigger AS
$BODY$
DECLARE
    audit_row audit.logged_actions;
    website_row audit.logged_actions_websites;
    excluded_cols text[] = ARRAY[]::text[];
    websiteIDs BIGINT[];
    websiteID BIGINT;
    lastsequenceval   BIGINT;
    _kind CHAR;
    thisRow RECORD;
BEGIN
    IF TG_WHEN <> 'AFTER' THEN
        RAISE EXCEPTION 'audit.if_modified_func() may only run as an AFTER trigger';
    END IF;

    -- Need to preserve LASTVAL() value as used in ORM, as NEXTVAL() in audit row would overwrite it.
    -- But only do this if doing a ROW level INSERT
    IF (TG_OP = 'INSERT' AND TG_LEVEL = 'ROW') THEN
        lastsequenceval = LASTVAL();
    END IF;

    IF (TG_OP = 'DELETE') THEN
        thisRow = OLD;
    ELSE
        thisRow = NEW;
    END IF;
    IF TG_OP = 'TRUNCATE' THEN
        audit_row = ROW(
            NEXTVAL('audit.logged_actions_id_seq'),       -- id
            txid_current(),                               -- transaction ID
            CURRENT_TIMESTAMP,                            -- action_tstamp_tx
            TG_TABLE_SCHEMA::text,                        -- schema_name
            TG_TABLE_NAME::text,                          -- event_table_name
            SUBSTRING(TG_OP,1,1),                         -- action
            'f',                                          -- statement_only
            NULL,                                         -- Indicia row primary key
            TG_TABLE_NAME::text,                          -- Indicia row search_table_name
            NULL,                                         -- Indicia search_key
            session_user::text,                           -- Indicia session_user_name
            NULL,                                         -- Indicia updated_by_id
            NULL, NULL                                    -- row_data, changed_fields
        );

    ELSE
        audit_row = ROW(
            NEXTVAL('audit.logged_actions_id_seq'),       -- id
            txid_current(),                               -- transaction ID
            CURRENT_TIMESTAMP,                            -- action_tstamp_tx
            TG_TABLE_SCHEMA::text,                        -- schema_name
            TG_TABLE_NAME::text,                          -- event_table_name
            SUBSTRING(TG_OP,1,1),                         -- action
            'f',                                          -- statement_only
            thisRow.id,                                   -- Indicia row primary key
            TG_TABLE_NAME::text,                          -- Indicia row search_table_name
            thisRow.id,                                   -- Indicia search_key
            session_user::text,                           -- Indicia session_user_name
            thisRow.updated_by_id,                        -- Indicia updated_by_id
            NULL, NULL                                    -- row_data, changed_fields
        );

    END IF;

    IF (TG_OP = 'INSERT' AND TG_LEVEL = 'ROW') THEN
      -- Temporary sequences are dropped automatically at end of session
      -- Have to leave it existing after function end in order for the LASTVAL call in ORM to work
      -- Possibly have multiple e.g. attributes created and audited in same session.
      BEGIN
        CREATE TEMPORARY SEQUENCE audit_dummy_sequence;
      EXCEPTION WHEN duplicate_table THEN
        -- do nothing
      END;
      -- Set value so can be used for NEXTVAL()
      -- Dont set in sequence CREATE statement : this sets it even if sequence already exists
      -- FALSE means next NEXTVAL call will return lastsequenceval
      PERFORM SETVAL('audit_dummy_sequence', lastsequenceval, FALSE);
      -- reset the value returned by LASTVAL()
      PERFORM NEXTVAL('audit_dummy_sequence');
    END IF;

    --- Override search details for child records
    IF (TG_ARGV[0] = 'sample_id' AND TG_OP <> 'TRUNCATE') THEN
    	audit_row.search_table_name = 'samples';
    	audit_row.search_key = thisRow.sample_id;
    ELSIF (TG_ARGV[0] = 'occurrence_id' AND TG_OP <> 'TRUNCATE') THEN
    	audit_row.search_table_name = 'occurrences';
    	audit_row.search_key = thisRow.occurrence_id;
    ELSIF (TG_ARGV[0] = 'location_id' AND TG_OP <> 'TRUNCATE') THEN
    	audit_row.search_table_name = 'locations';
    	audit_row.search_key = thisRow.location_id;
    END IF;

    IF TG_ARGV[1] IS NOT NULL THEN
        excluded_cols = TG_ARGV[1]::text[];
    END IF;

    IF (TG_OP = 'UPDATE' AND TG_LEVEL = 'ROW') THEN
        audit_row.row_data = hstore(OLD.*);
        audit_row.changed_fields =  (hstore(NEW.*) - audit_row.row_data) - excluded_cols;
        IF audit_row.changed_fields = hstore('') THEN
            -- All changed fields are ignored. Skip this update.
            RETURN NULL;
        END IF;
        websiteIDs = audit.get_indicia_websites(audit_row.search_table_name::text, TG_ARGV[0]::text, OLD) ||
        	audit.get_indicia_websites(audit_row.search_table_name::text, TG_ARGV[0]::text, NEW);
    ELSIF (TG_OP = 'DELETE' AND TG_LEVEL = 'ROW') THEN
        audit_row.row_data = hstore(OLD.*) - excluded_cols;
        websiteIDs = audit.get_indicia_websites(audit_row.search_table_name::text, TG_ARGV[0]::text, OLD);
    ELSIF (TG_OP = 'INSERT' AND TG_LEVEL = 'ROW') THEN
        audit_row.row_data = hstore(NEW.*) - excluded_cols;
        websiteIDs = audit.get_indicia_websites(audit_row.search_table_name::text, TG_ARGV[0]::text, NEW);
    ELSIF (TG_LEVEL = 'STATEMENT' AND TG_OP IN ('INSERT','UPDATE','DELETE','TRUNCATE')) THEN
        audit_row.statement_only = 't';
    ELSE
        RAISE EXCEPTION '[audit.if_modified_func] - Trigger func added as trigger for unhandled case: %, %',TG_OP, TG_LEVEL;
        RETURN NULL;
    END IF;
    INSERT INTO audit.logged_actions VALUES (audit_row.*) RETURNING id INTO website_row.logged_action_id;
    FOR websiteID IN SELECT DISTINCT UNNEST(websiteIDs)
    LOOP
        website_row.website_id = websiteID;
    	INSERT INTO audit.logged_actions_websites VALUES (website_row.*);
    END LOOP;

    RETURN NULL;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE SECURITY DEFINER
  COST 100;