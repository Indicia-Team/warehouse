CREATE OR REPLACE function f_add_new_survey_fields (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE surveys ADD COLUMN auto_accept boolean NOT NULL DEFAULT false;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE surveys
    ADD COLUMN auto_accept_max_difficulty integer CHECK (auto_accept_max_difficulty >= 1 AND auto_accept_max_difficulty <= 5);

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_new_survey_fields();

DROP FUNCTION f_add_new_survey_fields();

COMMENT ON COLUMN surveys.auto_accept IS 'Automatically accept records if all verification rules pass.';
COMMENT ON COLUMN surveys.auto_accept_max_difficulty IS 'The maximum ID difficulty to auto-accept records for.';
