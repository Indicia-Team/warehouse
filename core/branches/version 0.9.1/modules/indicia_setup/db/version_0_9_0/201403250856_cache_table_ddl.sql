CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN kingdom_taxa_taxon_list_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN order_taxa_taxon_list_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN family_taxa_taxon_list_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN kingdom_taxon character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN order_taxon character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN family_taxon character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN cache_taxa_taxon_lists.kingdom_taxa_taxon_list_id IS 'Preferred taxa_taxon_list record which identifies the Kingdom of this taxon';
COMMENT ON COLUMN cache_taxa_taxon_lists.order_taxa_taxon_list_id IS 'Preferred taxa_taxon_list record which identifies the Order of this taxon';
COMMENT ON COLUMN cache_taxa_taxon_lists.family_taxa_taxon_list_id IS 'Preferred taxa_taxon_list record which identifies the Family of this taxon';
COMMENT ON COLUMN cache_taxa_taxon_lists.kingdom_taxon IS 'The taxon name of the Kingdom containing this taxon';
COMMENT ON COLUMN cache_taxa_taxon_lists.order_taxon IS 'The taxon name of the Order containing this taxon';
COMMENT ON COLUMN cache_taxa_taxon_lists.family_taxon IS 'The taxon name of the Family containing this taxon';

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_kingdom_taxa_taxon_list_id;
CREATE INDEX ix_cache_taxa_taxon_lists_kingdom_taxa_taxon_list_id
  ON cache_taxa_taxon_lists
  USING btree
  (kingdom_taxa_taxon_list_id);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_order_taxa_taxon_list_id;
CREATE INDEX ix_cache_taxa_taxon_lists_order_taxa_taxon_list_id
  ON cache_taxa_taxon_lists
  USING btree
  (order_taxa_taxon_list_id);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_family_taxa_taxon_list_id;
CREATE INDEX ix_cache_taxa_taxon_lists_family_taxa_taxon_list_id
  ON cache_taxa_taxon_lists
  USING btree
  (family_taxa_taxon_list_id);
  
DROP INDEX IF EXISTS fki_taxon_searchterms_taxon_list; -- redundant version of this index
DROP INDEX IF EXISTS ix_cache_taxon_searchterms_taxon_list;
CREATE INDEX ix_cache_taxon_searchterms_taxon_list ON cache_taxon_searchterms(taxon_list_id);  
