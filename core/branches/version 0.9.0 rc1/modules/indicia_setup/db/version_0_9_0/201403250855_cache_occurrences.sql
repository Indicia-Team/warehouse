CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
CREATE TABLE cache_occurrences
(
  id integer NOT NULL,
  record_status character(1),
  downloaded_flag character(1),
  zero_abundance boolean,
  website_id integer,
  survey_id integer,
  sample_id integer,
  survey_title character varying(100),
  date_start date,
  date_end date,
  date_type character varying(2),
  public_entered_sref character varying,
  entered_sref_system character varying(10),
  public_geom geometry,
  sample_method character varying,
  taxa_taxon_list_id integer,
  preferred_taxa_taxon_list_id integer,
  taxonomic_sort_order bigint,
  taxon character varying,
  authority character varying(100),
  preferred_taxon character varying,
  preferred_authority character varying(100),
  default_common_name character varying,
  search_name character varying,
  taxa_taxon_list_external_key character varying(50),
  taxon_meaning_id integer,
  taxon_group_id integer,
  taxon_group character varying(100),
  created_by_id integer,
  cache_created_on timestamp without time zone NOT NULL,
  cache_updated_on timestamp without time zone NOT NULL,
  CONSTRAINT pk_cache_occurrences PRIMARY KEY (id)
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
ALTER TABLE cache_occurrences ADD COLUMN certainty character(1);
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN location_name character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN recorders character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN verifier character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN images character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD training boolean NOT NULL default false;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN location_id integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN input_form character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN data_cleaner_info character varying;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN release_status character(1);
ALTER TABLE cache_occurrences ALTER COLUMN release_status SET DEFAULT 'R'::bpchar;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN verified_on timestamp without time zone;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN sensitivity_precision integer;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

-- required for ensuring all upgrade paths work
ALTER TABLE cache_occurrences ALTER taxonomic_sort_order TYPE bigint;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN cache_occurrences.certainty IS 'Certainty of the record as indicated by the recorder. Options are C (certain), L (likely) and U (uncertain).';
COMMENT ON COLUMN cache_occurrences.location_name IS 'Location name, either from the linked location or from the location_name field in sample.';
COMMENT ON COLUMN cache_occurrences.recorders IS 'Recorder username, name or names';
COMMENT ON COLUMN cache_occurrences.verifier IS 'Name of the record''s verifier, if any.';
COMMENT ON COLUMN cache_occurrences.images IS 'Comma separated list of image paths for this occurrence''s images.';
COMMENT ON COLUMN cache_occurrences.training IS 'Flag indicating if this record was created for training purposes and is therefore not considered real.';
COMMENT ON COLUMN cache_occurrences.release_status IS 'Release states of this record. R - released, P - recorder has requested a precheck before release, U - unreleased as part of a project whcih is witholding records until completion.';
COMMENT ON COLUMN cache_occurrences.verified_on IS 'Date this record had it''s verification status changed.';

DROP INDEX IF EXISTS ix_cache_occurrences_created_by_id;
CREATE INDEX ix_cache_occurrences_created_by_id
  ON cache_occurrences
  USING btree
  (created_by_id);

DROP INDEX IF EXISTS ix_cache_occurrences_date_start;
CREATE INDEX ix_cache_occurrences_date_start
  ON cache_occurrences
  USING btree
  (date_start);

DROP INDEX IF EXISTS ix_cache_occurrences_geom;
CREATE INDEX ix_cache_occurrences_geom
  ON cache_occurrences
  USING gist
  (public_geom);

DROP INDEX IF EXISTS ix_cache_occurrences_record_status;
CREATE INDEX ix_cache_occurrences_record_status
  ON cache_occurrences
  USING btree
  (record_status);

DROP INDEX IF EXISTS ix_cache_occurrences_survey_id;
CREATE INDEX ix_cache_occurrences_survey_id
  ON cache_occurrences
  USING btree
  (survey_id);

DROP INDEX IF EXISTS ix_cache_occurrences_website_id;
CREATE INDEX ix_cache_occurrences_website_id
  ON cache_occurrences
  USING btree
  (website_id);

DROP INDEX IF EXISTS ix_occurrences_preferred_taxa_taxon_list_id;
CREATE INDEX ix_occurrences_preferred_taxa_taxon_list_id
  ON cache_occurrences
  USING btree
  (preferred_taxa_taxon_list_id);

DROP INDEX IF EXISTS ix_occurrences_search_name;
CREATE INDEX ix_occurrences_search_name
  ON cache_occurrences
  USING btree
  (search_name);
  
DROP INDEX IF EXISTS ix_occurrences_taxon_meaning_id;
CREATE INDEX ix_occurrences_taxon_meaning_id
  ON cache_occurrences
  USING btree
  (taxon_meaning_id);

DROP INDEX IF EXISTS ix_cache_occurrences_sample_id;
CREATE INDEX ix_cache_occurrences_sample_id ON cache_occurrences(sample_id);

DROP INDEX IF EXISTS ix_cache_occurrences_sample_id;
CREATE INDEX ix_cache_occurrences_sample_id
    ON cache_occurrences
    USING btree
    (taxa_taxon_list_external_key);