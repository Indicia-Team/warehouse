COMMENT ON TABLE groups IS 'A list of entities which involve groupings of people, such as recording groups, projects or organisations.';

ALTER TABLE groups
  ADD CONSTRAINT groups_joining_method_check CHECK (joining_method = ANY (ARRAY['P'::bpchar, 'R'::bpchar, 'I'::bpchar, 'A'::bpchar]));

ALTER TABLE groups
   ADD COLUMN code character varying(20);

ALTER TABLE groups
   ADD COLUMN group_type_id integer;

ALTER TABLE groups
   ADD COLUMN from_date date;

ALTER TABLE groups
   ADD COLUMN to_date date;

ALTER TABLE groups
   ADD COLUMN private_records boolean default false;

ALTER TABLE groups
  ADD CONSTRAINT fk_group_type FOREIGN KEY (group_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION;

COMMENT ON COLUMN groups.code IS 'A code or abbreviation identifying the group.';
COMMENT ON COLUMN groups.group_type_id IS 'Foreign key to the termlists_terms table. Identifies the type of group, e.g. recording group, project, organisation.';
COMMENT ON COLUMN groups.from_date IS 'Date the group''s activities commenced if relevent, e.g. a project start date.';
COMMENT ON COLUMN groups.to_date IS 'Date the group''s activities ceased if relevent, e.g. a project finish date.';
COMMENT ON COLUMN groups.private_records IS 
    'Set to true to indicate that the records input which are directly linked to the group should be witheld from uses outside the group. Relies on reporting queries to respect this.'; 

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
)
;

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
VALUES ('Group relationship types', 'List of group relationship types.', now(), 1, now(), 1, 'indicia:group_relationship_types');

SELECT insert_term('custodian of', 'eng', null, 'indicia:group_relationship_types');
SELECT insert_term('client of', 'eng', null, 'indicia:group_relationship_types');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Group types', 'List of group types.', now(), 1, now(), 1, 'indicia:group_types');

SELECT insert_term('Recording group', 'eng', null, 'indicia:group_types');
SELECT insert_term('Project', 'eng', null, 'indicia:group_types');
SELECT insert_term('Organisation', 'eng', null, 'indicia:group_types');

-- all existing groups will be recording groups
UPDATE groups g
SET group_type_id=t.id
FROM list_termlists_terms t
WHERE t.termlist_external_key='indicia:group_types'
AND t.term='Recording group';

ALTER TABLE groups
   ALTER COLUMN group_type_id SET NOT NULL;

DROP VIEW list_groups;

CREATE OR REPLACE VIEW list_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, g.website_id, g.filter_id
   FROM groups g
  WHERE g.deleted = false;

DROP VIEW detail_groups;

CREATE OR REPLACE VIEW detail_groups AS 
 SELECT g.id, g.title, g.code, g.group_type_id, g.description, g.from_date, g.to_date, g.private_records, 
     g.website_id, g.joining_method, g.filter_id, f.definition as filter_definition, 
     g.created_by_id, c.username AS created_by, g.updated_by_id, u.username AS updated_by
   FROM groups g
   LEFT JOIN filters f ON f.id=g.filter_id AND f.deleted=false
   JOIN indicia.users c ON c.id = g.created_by_id
   JOIN indicia.users u ON u.id = g.updated_by_id
  WHERE g.deleted = false;

CREATE OR REPLACE VIEW list_groups_users AS 
 SELECT gu.id, gu.group_id, gu.user_id, gu.administrator, g.website_id, u.username
   FROM groups_users gu
   JOIN groups g on g.id=gu.group_id AND g.deleted=false
   JOIN users u on u.id=gu.user_id
  WHERE gu.deleted = false;

CREATE OR REPLACE VIEW detail_groups_users AS 
 SELECT gu.id, gu.group_id, gu.user_id, gu.administrator, g.website_id,
    u.username, p.first_name, p.surname, p.surname || COALESCE(', ' || p.first_name, '') as person_name,
    gu.created_by_id, c.username AS created_by, gu.updated_by_id, up.username AS updated_by
   FROM groups_users gu
   JOIN groups g on g.id=gu.group_id AND g.deleted=false
   JOIN users u ON u.id=gu.user_id AND u.deleted=false
   JOIN people p ON p.id=u.person_id AND p.deleted=false
   JOIN users c ON c.id = gu.created_by_id
   JOIN users up ON up.id = gu.updated_by_id
  WHERE gu.deleted = false;