-- #slow script#

CREATE OR REPLACE FUNCTION set_oc_confidential()
  RETURNS boolean AS
$BODY$
  DECLARE audit boolean;
  DECLARE test regproc;
BEGIN

  BEGIN
    SELECT to_regproc('audit.audit_table') INTO test;
    DROP TRIGGER IF EXISTS audit_trigger_row ON occurrence_comments;
    DROP TRIGGER IF EXISTS audit_trigger_stm ON occurrence_comments;
    audit := TRUE;
    RAISE INFO 'audit active';
  EXCEPTION
    WHEN OTHERS THEN
      audit := FALSE;
      RAISE INFO 'audit inactive';
  END;

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