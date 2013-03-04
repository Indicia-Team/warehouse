CREATE OR REPLACE function f_add_training (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := FALSE;

BEGIN
  ALTER TABLE occurrences ADD training boolean NOT NULL default false;
  success := TRUE;
EXCEPTION
    WHEN duplicate_column THEN RAISE NOTICE 'column exists.';
END;

COMMENT ON COLUMN occurrences.training IS 'Flag indicating if this record was created for training purposes and is therefore not considered real.';

END
$func$;

SELECT f_add_training();

DROP FUNCTION f_add_training();