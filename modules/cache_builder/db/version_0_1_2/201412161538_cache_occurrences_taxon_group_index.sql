CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
CREATE INDEX ix_cache_occurrences_taxon_group_id
  ON cache_occurrences
  USING btree
  (taxon_group_id);
EXCEPTION
    WHEN OTHERS THEN 
      RAISE NOTICE 'index exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();


  
  