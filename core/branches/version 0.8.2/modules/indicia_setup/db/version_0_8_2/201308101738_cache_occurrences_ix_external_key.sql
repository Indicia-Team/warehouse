CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
 
IF NOT EXISTS (
  SELECT 1 FROM pg_class c WHERE  c.relname = 'ix_cache_occurrences_taxa_taxon_list_external_key'
) THEN

  CREATE INDEX ix_cache_occurrences_taxa_taxon_list_external_key
    ON cache_occurrences
    USING btree
    (taxa_taxon_list_external_key);
  
END IF; 

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();
