CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
CREATE TABLE cache_taxa_taxon_lists
(
  id integer NOT NULL,
  preferred boolean,
  taxon_list_id integer,
  taxon_list_title character varying,
  website_id integer,
  preferred_taxa_taxon_list_id integer,
  parent_id integer,
  taxonomic_sort_order bigint,
  taxon character varying,
  authority character varying(100),
  language_iso character varying(3),
  "language" character varying(50),
  preferred_taxon character varying,
  preferred_authority character varying(100),
  preferred_language_iso character varying(3),
  preferred_language character varying(50),
  default_common_name character varying,
  search_name character varying, -- simplified version of the taxon for searching. No punctuation, spaces.
  external_key character varying(50),
  taxon_meaning_id integer,
  taxon_group_id integer,
  taxon_group character varying(100),
  cache_created_on timestamp without time zone NOT NULL,
  cache_updated_on timestamp without time zone NOT NULL,
  CONSTRAINT pk_cache_taxa_taxon_lists PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

-- The following columns must be added separately to the initial table create, as this script tidies up a messy upgrade
-- path with the table created in several stages.

BEGIN
ALTER TABLE cache_taxa_taxon_lists ADD COLUMN allow_data_entry boolean DEFAULT true;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON TABLE cache_taxa_taxon_lists IS 'A cache of all taxon_list_taxa entries including joins to the most likely fields to be required of any query. Updated when the scheduled_tasks action is called so should not be used when fully up to date information is required.';

/* Corrections for upgrade paths which had installed prior versions of the table before it was integrated in core */

-- Correct field size for users which had installed previous versions of the table
ALTER TABLE cache_taxa_taxon_lists ALTER taxonomic_sort_order TYPE bigint;

/* Create indexes */

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_language_iso;
CREATE INDEX ix_cache_taxa_taxon_lists_language_iso
  ON cache_taxa_taxon_lists
  USING btree
  (language_iso);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_parent_id;
CREATE INDEX ix_cache_taxa_taxon_lists_parent_id
  ON cache_taxa_taxon_lists
  USING btree
  (parent_id);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_taxonomic_sort_order;
CREATE INDEX ix_cache_taxa_taxon_lists_taxonomic_sort_order
  ON cache_taxa_taxon_lists
  USING btree
  (taxonomic_sort_order);
  
DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_taxon;
CREATE INDEX ix_cache_taxa_taxon_lists_taxon
  ON cache_taxa_taxon_lists
  USING btree
  (taxon);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_taxon_list_id;
CREATE INDEX ix_cache_taxa_taxon_lists_taxon_list_id
  ON cache_taxa_taxon_lists
  USING btree
  (taxon_list_id);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_search_name;
CREATE INDEX ix_cache_taxa_taxon_lists_search_name
  ON cache_taxa_taxon_lists
  USING btree
  (search_name);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_taxon_meaning_id;
CREATE INDEX ix_cache_taxa_taxon_lists_taxon_meaning_id
  ON cache_taxa_taxon_lists
  USING btree
  (taxon_meaning_id);

DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_taxon_group_id;
CREATE INDEX ix_cache_taxa_taxon_lists_taxon_group_id
  ON cache_taxa_taxon_lists
  USING btree
  (taxon_group_id);
  
DROP INDEX IF EXISTS ix_cache_taxa_taxon_lists_updated_on;
CREATE INDEX ix_cache_taxa_taxon_lists_updated_on ON cache_taxa_taxon_lists(cache_updated_on);