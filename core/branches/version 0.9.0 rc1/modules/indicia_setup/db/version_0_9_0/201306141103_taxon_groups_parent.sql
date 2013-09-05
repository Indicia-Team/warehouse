CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN

	ALTER TABLE taxon_groups ADD COLUMN parent_id integer;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN

	ALTER TABLE taxon_groups ADD COLUMN description CHARACTER VARYING;

EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

COMMENT ON COLUMN taxon_groups.parent_id IS 'Identifies the parent of the taxon group if there is one.';
COMMENT ON COLUMN taxon_groups.description IS 'Description of the taxon group.';

BEGIN

	ALTER TABLE taxon_groups ADD CONSTRAINT fki_taxon_group_parent FOREIGN KEY (parent_id) REFERENCES taxon_groups (id)
	   ON UPDATE NO ACTION ON DELETE NO ACTION;

EXCEPTION
    WHEN duplicate_object THEN 
      RAISE NOTICE 'constraint exists.';
      success := FALSE;
END;

DROP INDEX IF EXISTS fki_fki_taxon_group_parent;

CREATE INDEX fki_fki_taxon_group_parent ON taxon_groups(parent_id);

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();