CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN

ALTER TABLE samples
   ADD COLUMN input_form character varying;
   
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN samples.input_form IS 'Identifier of the input form used to create the record, allowing the client website to use the same form when editing. It is suggested that this is used to store the path to the form (either the complete URL or a partial path).';
