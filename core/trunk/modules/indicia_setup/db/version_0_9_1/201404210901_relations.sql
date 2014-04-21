CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
  CREATE TABLE group_relations
  (
    id serial NOT NULL, 
    from_group_id integer NOT NULL,
    to_group_id integer NOT NULL,
    relationship_type_id integer NOT NULL,
    created_on timestamp without time zone NOT NULL, -- Date this record was created.
    created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
    updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
    updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
    deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
    CONSTRAINT pk_group_relations PRIMARY KEY (id),
    CONSTRAINT fk_group__relation_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group__relation_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_relation_from FOREIGN KEY (from_group_id)
        REFERENCES groups (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_relation_to FOREIGN KEY (to_group_id)
        REFERENCES groups (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION,
    CONSTRAINT fk_group_relationship_type FOREIGN KEY (relationship_type_id)
        REFERENCES termlists_terms (id) MATCH SIMPLE
        ON UPDATE NO ACTION ON DELETE NO ACTION
  ) 
  WITH (
    OIDS = FALSE
  );
EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON COLUMN group_relations.id IS 'Primary key and unique identifier for the table';
COMMENT ON COLUMN group_relations.from_group_id IS 'Foreign key to the groups table. Identifies the group the relationship is from.';
COMMENT ON COLUMN group_relations.to_group_id IS 'Foreign key to the groups table. Identifies the group the relationship is to.';
COMMENT ON COLUMN group_relations.relationship_type_id IS 'Foreign key to the termlists_terms table. Identifies the term that describes the relationship.';
COMMENT ON COLUMN group_relations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN group_relations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN group_relations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN group_relations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN group_relations.deleted IS 'Has this record been deleted?';

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
  SELECT 'Group relationship types', 'List of group relationship types.', now(), 1, now(), 1, 'indicia:group_relationship_types' 
  WHERE 'indicia:group_relationship_types' NOT IN (SELECT external_key FROM termlists WHERE external_key='indicia:group_relationship_types');

SELECT insert_term('custodian of', 'eng', null, 'indicia:group_relationship_types');
SELECT insert_term('client of', 'eng', null, 'indicia:group_relationship_types');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
  SELECT 'Group types', 'List of group types.', now(), 1, now(), 1, 'indicia:group_types'
  WHERE 'indicia:group_types' NOT IN (SELECT external_key FROM termlists WHERE external_key='indicia:group_types');
  
SELECT insert_term('Recording group', 'eng', null, 'indicia:group_types');
SELECT insert_term('Project', 'eng', null, 'indicia:group_types');
SELECT insert_term('Organisation', 'eng', null, 'indicia:group_types');

-- all existing groups will be recording groups
UPDATE groups g
SET group_type_id=t.id
FROM list_termlists_terms t
WHERE t.termlist_external_key='indicia:group_types'
AND t.term='Recording group'
AND g.group_type_id IS NULL;

ALTER TABLE groups
   ALTER COLUMN group_type_id SET NOT NULL;