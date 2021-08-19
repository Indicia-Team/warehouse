CREATE OR REPLACE function f_add_new_survey_fields (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE surveys ADD COLUMN auto_accept_taxa_filters INT[];

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_new_survey_fields();

DROP FUNCTION f_add_new_survey_fields();

COMMENT ON COLUMN surveys.auto_accept_taxa_filters IS 'List of taxon meaning IDs to filter records qualifying for auto-verification';
