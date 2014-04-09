-- Because an incorrect column deletion was committed to trunk, recreate it for any dev warehouses which had run the update
-- before it was removed.
CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE occurrences ADD COLUMN last_verification_check_date timestamp;
	COMMENT ON COLUMN occurrences.last_verification_check_date IS 'Date & time that verification checks were last run on this occurrence, if any.';

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();