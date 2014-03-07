CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN

-- Table: taxon_ranks

-- DROP TABLE taxon_ranks;

CREATE TABLE taxon_ranks
(
  id serial NOT NULL, -- Primary key and unique identifier.
  rank character varying(50) NOT NULL, -- The name of the rank.
  short_name character varying(20) NOT NULL, -- Shortened version of the rank's name.
  italicise_taxon boolean NOT NULL DEFAULT false, -- If true, then formal taxon names of this rank should be displayed in italics.
  sort_order integer, -- Ordering of the ranks. 
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_taxon_ranks PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

COMMENT ON TABLE taxon_ranks
  IS 'A list of taxonomy ranks, such as phylum, class, species etc.';
COMMENT ON COLUMN taxon_ranks.id IS 'Primary key and unique identifier.';
COMMENT ON COLUMN taxon_ranks.rank IS 'The name of the rank.';
COMMENT ON COLUMN taxon_ranks.short_name IS 'Shortened version of the rank''s name.';
COMMENT ON COLUMN taxon_ranks.italicise_taxon IS 'If true, then formal taxon names of this rank should be displayed in italics.';
COMMENT ON COLUMN taxon_ranks.sort_order IS 'Ordering of the ranks.';
COMMENT ON COLUMN taxon_ranks.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_ranks.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_ranks.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_ranks.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN taxon_ranks.deleted IS 'Has this record been deleted?';

BEGIN

ALTER TABLE taxa ADD COLUMN taxon_rank_id integer;
COMMENT ON COLUMN taxa.taxon_rank_id IS 'Foreign key to the taxon_ranks table. Identifies the rank of the taxon (e.g. species, phylum). ';

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

IF NOT EXISTS(SELECT 1 FROM pg_constraint WHERE conname='fk_taxa_taxon_ranks') THEN
  ALTER TABLE taxa ADD CONSTRAINT fk_taxa_taxon_ranks FOREIGN KEY (taxon_rank_id) REFERENCES taxon_ranks (id)
   ON UPDATE NO ACTION ON DELETE NO ACTION;

  CREATE INDEX fki_taxa_taxon_ranks ON taxa(taxon_rank_id);
END IF;

BEGIN

ALTER TABLE taxa ADD COLUMN attribute character varying(100);
COMMENT ON COLUMN taxa.attribute IS 'Attributes such as sensu lato that are associated with the taxon name.';

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();