-- #slow script#

CREATE OR REPLACE FUNCTION set_oc_confidential()
  RETURNS boolean AS
$BODY$
  DECLARE audit boolean;
  DECLARE test regproc;
BEGIN

  audit := FALSE;
  IF EXISTS (
      SELECT 1
      FROM   information_schema.tables
      WHERE  table_schema = 'audit'
      AND    table_name = 'audit_table'
    ) THEN
    DROP TRIGGER IF EXISTS audit_trigger_row ON occurrence_comments;
    DROP TRIGGER IF EXISTS audit_trigger_stm ON occurrence_comments;
    audit := TRUE;
    RAISE INFO 'audit active';
  END IF;

  UPDATE occurrence_comments SET confidential = 'f';

  ALTER TABLE occurrence_comments
    ALTER COLUMN confidential SET DEFAULT FALSE,
    ALTER COLUMN confidential SET NOT NULL;

  IF audit THEN
    PERFORM audit.audit_table('occurrence_comments', true, 'occurrence_id');
  END IF;

  RETURN audit;
END
$BODY$
LANGUAGE 'plpgsql';

SELECT set_oc_confidential();

DROP FUNCTION set_oc_confidential();