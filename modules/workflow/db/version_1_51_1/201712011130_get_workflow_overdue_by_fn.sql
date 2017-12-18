/* Drop version with a typo */
DROP FUNCTION IF EXISTS get_worfklow_overdue_by(character, integer, timestamp without time zone, integer);

/**
 * For a given record status and creation date, calculates how overdue checking it is according to the
 * workflow module.
*/
CREATE OR REPLACE FUNCTION get_workflow_overdue_by(
    record_status character,
    record_substatus integer,
    query character,
    created_on timestamp without time zone,
    verification_delay_hours integer
    )
  RETURNS interval AS
$BODY$
BEGIN

  RETURN CASE
    WHEN record_status <> 'C' OR record_substatus IS NOT NULL OR query='Q' THEN NULL
    ELSE now() - (created_on + (verification_delay_hours::varchar || ' hours')::interval)
  END;

END
$BODY$
  LANGUAGE plpgsql IMMUTABLE
  COST 100;