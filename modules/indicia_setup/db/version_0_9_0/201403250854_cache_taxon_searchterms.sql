CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
CREATE TABLE cache_taxon_searchterms
(
  id serial NOT NULL, -- Unique identifier and primary key for the table.
  taxa_taxon_list_id integer NOT NULL, -- Identifies the taxon list entry which this searchable name applies to.
  taxon_list_id integer NOT NULL, -- Identifies the taxon list which this searchable name is from.
  searchterm character varying NOT NULL, -- Searchable identifier for the taxon. Includes taxon formal and vernacular names, simplified versions of these for searching and codes, abbreviations or other shortcuts used to lookup taxa.
  original character varying NOT NULL, -- When the term is simplified, provides the original unsimplified version of the term.
  taxon_group character varying NOT NULL, -- Name of the taxon group.
  taxon_meaning_id integer NOT NULL, -- Identifies the taxon meaning associated with this name. All names with the same ID refer to the same taxon.
  preferred_taxon character varying NOT NULL, -- Provides the preferred taxon name for a taxon that has been looked up,
  default_common_name character varying, -- Provides the preferred common name for a taxon that has been looked up,
  preferred_authority character varying, -- The taxonomic authority of the preferred taxon name.
  language_iso character varying, -- The language associated with the search term, or null if not language specific.
  name_type character(1) NOT NULL, -- Type of taxon name string. Options are (L)atin, (S)ynonym, (V)ernacular, (O)ther vernacular name, (C)ode, (A)bbreviation.
  simplified boolean DEFAULT false, -- Is this a name which has been simplified make it tolerant of some spelling and punctuation errors when searching.
  code_type_id integer, -- For names which are codes, identifies the type of code. Foreign key to the termlists_terms table.
  source_id integer, -- When the search term is from a taxon_codes table record, provides the id of the record which the code was source from.  
  CONSTRAINT pk_cache_taxon_searchterms PRIMARY KEY (id),
  CONSTRAINT fk_taxon_searchterms_code_type_id FOREIGN KEY (code_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_searchterms_taxa_taxon_list FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_searchterms_taxon_meaning_id FOREIGN KEY (taxon_meaning_id)
      REFERENCES taxon_meanings (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_taxon_searchterms_name_type CHECK (name_type = ANY (ARRAY['L'::bpchar, 'S'::bpchar, 'V'::bpchar, 'O'::bpchar, 'C'::bpchar, 'A'::bpchar]))
)
WITH (
  OIDS=FALSE
);
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

/* 
Column additions
The following columns must be added separately to the initial table create, as this script tidies up a messy upgrade
*/

BEGIN
ALTER TABLE cache_taxon_searchterms ADD COLUMN taxon_group_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD COLUMN preferred boolean NULL;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD COLUMN searchterm_length integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD parent_id integer null;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD preferred_taxa_taxon_list_id integer null;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD COLUMN identification_difficulty integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_taxon_searchterms ADD COLUMN id_diff_verification_rule_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON TABLE cache_taxon_searchterms IS 'Provides a table with content optimised for searching for taxon name strings. There can be several searchable terms or other codes per taxon item.';
COMMENT ON COLUMN cache_taxon_searchterms.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN cache_taxon_searchterms.taxa_taxon_list_id IS 'Identifies the taxon list entry which this searchable name applies to.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_list_id IS 'Identifies the taxon list which this searchable name is from.';
COMMENT ON COLUMN cache_taxon_searchterms.searchterm IS 'Searchable identifier for the taxon. Includes taxon formal and vernacular names, simplified versions of these for searching and codes, abbreviations or other shortcuts used to lookup taxa.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_group IS 'Name of the taxon group.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_meaning_id IS 'Identifies the taxon meaning associated with this name. All names with the same ID refer to the same taxon.';
COMMENT ON COLUMN cache_taxon_searchterms.preferred_taxon IS 'Provides the preferred taxon name for a taxon that has been looked up,';
COMMENT ON COLUMN cache_taxon_searchterms.default_common_name IS 'Provides the preferred common name for a taxon that has been looked up,';
COMMENT ON COLUMN cache_taxon_searchterms.preferred_authority IS 'The taxonomic authority of the preferred taxon name.';
COMMENT ON COLUMN cache_taxon_searchterms.language_iso IS 'The language associated with the search term, or null if not language specific.';
COMMENT ON COLUMN cache_taxon_searchterms.name_type IS 'Type of taxon name string. Options are (L)atin, (S)ynonym, (V)ernacular, (O)ther vernacular name, (C)ode, (A)bbreviation.';
COMMENT ON COLUMN cache_taxon_searchterms.simplified IS 'Is this a name which has been simplified make it tolerant of some spelling and punctuation errors when searching.';
COMMENT ON COLUMN cache_taxon_searchterms.code_type_id IS 'For names which are codes, identifies the type of code. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN cache_taxon_searchterms.source_id IS 'When the search term is from a taxon_codes table record, provides the id of the record which the code was source from.';
COMMENT ON COLUMN cache_taxon_searchterms.original IS 'When the term is simplified, provides the original unsimplified version of the term.';
COMMENT ON COLUMN cache_taxon_searchterms.taxon_group_id IS 'ID of the taxon group';
COMMENT ON COLUMN cache_taxon_searchterms.preferred IS 'Does this entry represent a list-preferred name?';
COMMENT ON COLUMN cache_taxon_searchterms.searchterm_length IS 'Contains the length of the searchterm field, useful for taxon name searches. Putting shorter searchterms at the top of a list brings the "nearest" matches to the top.';
COMMENT ON COLUMN cache_taxon_searchterms.parent_id IS 'Identifies the parent of the taxon in the hierarchy, if one exists. ';
COMMENT ON COLUMN cache_taxon_searchterms.preferred_taxa_taxon_list_id IS 'ID of the preferred version of this term.';
COMMENT ON COLUMN cache_taxon_searchterms.identification_difficulty IS 'Identification difficulty assigned by the data_cleaner module, on a scale from 1 (easy) to 5 (difficult)';
COMMENT ON COLUMN cache_taxon_searchterms.id_diff_verification_rule_id  IS 'Verification rule that is associated with the identification difficulty.';

DROP INDEX IF EXISTS fki_taxon_searchterms_code_type_id;
CREATE INDEX fki_taxon_searchterms_code_type_id
  ON cache_taxon_searchterms
  USING btree
  (code_type_id);

DROP INDEX IF EXISTS fki_taxon_searchterms_taxa_taxon_list;
CREATE INDEX fki_taxon_searchterms_taxa_taxon_list
  ON cache_taxon_searchterms
  USING btree
  (taxa_taxon_list_id);

DROP INDEX IF EXISTS fki_taxon_searchterms_taxon_meaning_id;
CREATE INDEX fki_taxon_searchterms_taxon_meaning_id
  ON cache_taxon_searchterms
  USING btree
  (taxon_meaning_id);

DROP INDEX IF EXISTS ix_cache_taxon_searchterms_searchterm;
CREATE INDEX ix_cache_taxon_searchterms_searchterm
  ON cache_taxon_searchterms
  USING btree
  (searchterm varchar_pattern_ops);
  
  

