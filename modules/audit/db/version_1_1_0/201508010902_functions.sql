CREATE OR REPLACE FUNCTION audit.get_indicia_websites(tableName text, keyField text, tableRow anyelement) RETURNS BIGINT[] AS $body$
DECLARE
	websiteIDs BIGINT[];
	surveyID BIGINT;
	locatonID BIGINT;
BEGIN
	RAISE INFO 'audit.get_indicia_websites : tableName %  : keyfield %',tableName,keyField;
	IF (tableName = 'samples') THEN
 		--- samples link to website_id through row survey_id
 		IF (keyField = 'id') THEN
 			-- we have been handed a sample.
	    	surveyID = tableRow.survey_id;
	    	RAISE INFO 'audit.get_indicia_websites : Looking for surveyID %',surveyID;
 		ELSE
 			-- we have been given a child record, so need to look up the sample
 			SELECT survey_id INTO surveyID FROM samples WHERE id = tableRow.sample_id;
	    	RAISE INFO 'audit.get_indicia_websites : Looking for surveyID %',surveyID;
		END IF;
 		-- we have been given a child record, so need to look up the sample
 		SELECT ARRAY[website_id] INTO websiteIDs FROM surveys WHERE id = surveyID;
    ELSIF (tableName = 'occurrences') THEN
 		--- occurrences have website_id on the row
 		IF (keyField = 'id') THEN
 			-- we have been handed an occurrence.
	    	SELECT ARRAY[tableRow.website_id] INTO websiteIDs;
 		ELSE
 			SELECT ARRAY[website_id] INTO websiteIDs FROM occurrences WHERE id = tableRow.occurrence_id;
 		END IF;
    ELSIF (tableName = 'locations') THEN
 		--- locations link to website_id through locations_websites table, may have more than one record.
 		IF (keyField = 'id') THEN
 			-- we have been handed a sample.
	    	locatonID = tableRow.id;
 		ELSE
 			-- we have been given a child record, so need to look up the sample
 	    	locatonID = tableRow.location_id;
 		END IF;
 		-- we have been given a child record, so need to look up the sample
 		SELECT array(SELECT website_id FROM locations_websites WHERE location_id = locatonID AND deleted = 'f') INTO websiteIDs;
    END IF;
	RAISE INFO 'audit.get_indicia_websites : returning %',websiteIDs;
	return websiteIDs;
END;
$body$
LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION audit.if_modified_func() RETURNS TRIGGER AS $body$
DECLARE
    audit_row audit.logged_actions;
    website_row audit.logged_actions_websites;
    excluded_cols text[] = ARRAY[]::text[];
    websiteIDs BIGINT[];
    websiteID BIGINT;
BEGIN
    IF TG_WHEN <> 'AFTER' THEN
        RAISE EXCEPTION 'audit.if_modified_func() may only run as an AFTER trigger';
    END IF;

    audit_row = ROW(
        NEXTVAL('audit.logged_actions_id_seq'),       -- id
        txid_current(),                               -- transaction ID
        CURRENT_TIMESTAMP,                            -- action_tstamp_tx
        TG_TABLE_SCHEMA::text,                        -- schema_name
        TG_TABLE_NAME::text,                          -- event_table_name
        SUBSTRING(TG_OP,1,1),                         -- action
        'f',                                          -- statement_only
        NEW.id,                                       -- Indicia row primary key
        TG_TABLE_NAME::text,                          -- Indicia row search_table_name
        NEW.id,                                       -- Indicia search_key
        session_user::text,                           -- Indicia session_user_name
        NEW.updated_by_id,                            -- Indicia updated_by_id
        NULL, NULL                                    -- row_data, changed_fields
        );

	--- Override search details for child records
	IF (TG_ARGV[0] = 'sample_id') THEN
    	audit_row.search_table_name = 'samples';
    	audit_row.search_key = NEW.sample_id;
    ELSIF (TG_ARGV[0] = 'occurrence_id') THEN
    	audit_row.search_table_name = 'occurrences';
    	audit_row.search_key = NEW.occurrence_id;
    ELSIF (TG_ARGV[0] = 'location_id') THEN
    	audit_row.search_table_name = 'locations';
    	audit_row.search_key = NEW.location_id;
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
$body$
LANGUAGE plpgsql
SECURITY DEFINER;

COMMENT ON FUNCTION audit.if_modified_func() IS $body$
Track changes TO a TABLE at the statement AND/OR ROW level.

Optional parameters TO TRIGGER IN CREATE TRIGGER CALL:

param 0: text, COLUMN used to  source primary key; for subtables this
         will point to the parent records primary key.

param 1: text[], COLUMNS TO IGNORE IN updates. DEFAULT [].

         Updates TO ignored cols are omitted FROM changed_fields.

         Updates WITH ONLY ignored cols changed are NOT inserted
         INTO the audit log.

         Almost ALL the processing WORK IS still done FOR updates
         that ignored. IF you need TO save the LOAD, you need TO USE
         WHEN clause ON the TRIGGER instead.

         No warning OR error IS issued IF ignored_cols contains COLUMNS
         that do NOT exist IN the target TABLE. This lets you specify
         a standard SET OF ignored COLUMNS.

There IS no parameter TO disable logging OF VALUES. ADD this TRIGGER AS
a 'FOR EACH STATEMENT' rather than 'FOR EACH ROW' TRIGGER IF you do NOT
want TO log ROW VALUES.

Note that the USER name logged IS the login ROLE FOR the SESSION. The audit TRIGGER
cannot obtain the active ROLE because it IS reset BY the SECURITY DEFINER invocation
OF the audit TRIGGER its SELF.
$body$;



CREATE OR REPLACE FUNCTION audit.audit_table(target_table regclass, audit_rows BOOLEAN, audit_inserts BOOLEAN, primary_column text, ignored_cols text[]) RETURNS void AS $body$
DECLARE
  stm_targets text = 'UPDATE OR DELETE OR TRUNCATE';
  row_targets text = 'UPDATE OR DELETE';
  _q_txt text;
  _ignored_cols_snip text = '';
BEGIN
    EXECUTE 'DROP TRIGGER IF EXISTS audit_trigger_row ON ' || quote_ident(target_table::text);
    EXECUTE 'DROP TRIGGER IF EXISTS audit_trigger_stm ON ' || quote_ident(target_table::text);

    IF audit_inserts THEN
        stm_targets = 'INSERT OR ' || stm_targets ;
        row_targets = 'INSERT OR ' || row_targets;
    END IF;

    IF audit_rows THEN
        IF array_length(ignored_cols,1) > 0 THEN
            _ignored_cols_snip = ', ' || quote_literal(ignored_cols);
        END IF;
        _q_txt = 'CREATE TRIGGER audit_trigger_row AFTER ' || row_targets || ' ON ' ||
                 quote_ident(target_table::text) ||
                 ' FOR EACH ROW EXECUTE PROCEDURE audit.if_modified_func(' ||
                 primary_column || _ignored_cols_snip || ');';
        RAISE NOTICE '%',_q_txt;
        EXECUTE _q_txt;
        stm_targets = 'TRUNCATE';
    ELSE
    END IF;

    _q_txt = 'CREATE TRIGGER audit_trigger_stm AFTER ' || stm_targets || ' ON ' ||
             quote_ident(target_table::text) ||
             ' FOR EACH STATEMENT EXECUTE PROCEDURE audit.if_modified_func(' ||
             primary_column || ');';
    RAISE NOTICE '%',_q_txt;
    EXECUTE _q_txt;

END;
$body$
LANGUAGE 'plpgsql';

COMMENT ON FUNCTION audit.audit_table(regclass, BOOLEAN, BOOLEAN, text, text[]) IS $body$
ADD auditing support TO a TABLE.

Arguments:
   target_table:     TABLE name, schema qualified IF NOT ON search_path
   audit_rows:       Record each ROW CHANGE, OR ONLY audit at a statement level
   audit_inserts:    Record each INSERT
   parent_column:    In tables with a parent entity, the column name
   ignored_cols:     COLUMNS TO exclude FROM UPDATE diffs, IGNORE updates that CHANGE ONLY ignored cols.
$body$;


-- And provide a convenient call wrappers for the simplest cases
--
CREATE OR REPLACE FUNCTION audit.audit_table(target_table regclass, audit_inserts BOOLEAN, parent_column text) RETURNS void AS $$
SELECT audit.audit_table($1, true, audit_inserts, parent_column, ARRAY[]::text[]);
$$ LANGUAGE 'sql';

COMMENT ON FUNCTION audit.audit_table(regclass, BOOLEAN, text) IS $body$
ADD auditing support TO the given TABLE. Subtable - parent column as supplied, Rows Audited, inserts audited, No cols are ignored.
$body$;

CREATE OR REPLACE FUNCTION audit.audit_table(target_table regclass) RETURNS void AS $$
SELECT audit.audit_table($1, true, false, 'id', ARRAY[]::text[]);
$$ LANGUAGE 'sql';

COMMENT ON FUNCTION audit.audit_table(regclass) IS $body$
ADD auditing support TO the given TABLE. Not subtable so no inserts, Rows Audited, No cols are ignored.
$body$;