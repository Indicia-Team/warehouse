-- Recreate function using EXECUTE FUNCTION rather than deprecated EXECUTE PROCEDURE.

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
                 ' FOR EACH ROW EXECUTE FUNCTION audit.if_modified_func(' ||
                 primary_column || _ignored_cols_snip || ');';
        RAISE NOTICE '%',_q_txt;
        EXECUTE _q_txt;
        stm_targets = 'TRUNCATE';
    ELSE
    END IF;

    _q_txt = 'CREATE TRIGGER audit_trigger_stm AFTER ' || stm_targets || ' ON ' ||
             quote_ident(target_table::text) ||
             ' FOR EACH STATEMENT EXECUTE FUNCTION audit.if_modified_func(' ||
             primary_column || ');';
    RAISE NOTICE '%',_q_txt;
    EXECUTE _q_txt;

END;
$body$
LANGUAGE 'plpgsql';