CREATE OR REPLACE function f_alter_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	-- this constraint is unnecessary, because notifications can get deleted (e.g. when clearing notifications from previous automated check runs).
  ALTER TABLE user_email_notification_frequency_last_runs DROP CONSTRAINT fk_last_max_notification_id;
EXCEPTION
    WHEN undefined_object THEN 
      RAISE NOTICE 'constraint does not exist.';
      success := FALSE;
END;

END
$func$;

SELECT f_alter_ddl();

DROP FUNCTION f_alter_ddl();

