CREATE OR REPLACE function f_wrap_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
  ALTER TABLE filters DROP CONSTRAINT uc_filter_name;
EXCEPTION    
  WHEN undefined_object THEN
    RAISE NOTICE 'constraint does not exist';
END;

END
$func$;

SELECT f_wrap_ddl();

DROP INDEX IF EXISTS ix_filter_name_unique;
CREATE UNIQUE INDEX ix_filter_name_unique ON filters (title , sharing , created_by_id) WHERE deleted=false;