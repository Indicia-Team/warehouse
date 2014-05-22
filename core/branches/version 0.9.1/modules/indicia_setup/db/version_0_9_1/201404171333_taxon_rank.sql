CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
	ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank_id integer;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;
BEGIN
	ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank_sort_order integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_taxa_taxon_lists ADD COLUMN taxon_rank varchar;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
	ALTER TABLE cache_taxon_searchterms ADD COLUMN taxon_rank_sort_order integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

CREATE OR REPLACE VIEW gv_taxon_ranks AS 
 SELECT id, rank, sort_order
  FROM taxon_ranks
  WHERE deleted=false;

CREATE OR REPLACE VIEW list_taxon_ranks AS 
 SELECT id, rank, sort_order, short_name, italicise_taxon
  FROM taxon_ranks
  WHERE deleted=false;