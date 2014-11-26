-- Table: index_groups_taxon_groups

-- DROP TABLE index_groups_taxon_groups;

CREATE TABLE index_groups_taxon_groups
(
  id serial NOT NULL, -- Primary key and unique identifier for the table.
  group_id integer NOT NULL, -- Identifies the recording group that overlaps the taxon group. Foreign key to the groups table.
  taxon_group_id integer NOT NULL, -- Identifies the taxon group which the group overlaps. Foreign key to the taxon_groups table.
  CONSTRAINT pk_index_groups_taxon_groups PRIMARY KEY (id ),
  CONSTRAINT fk_index_groups_taxon_groups_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_index_groups_taxon_groups_taxon_group FOREIGN KEY (taxon_group_id)
      REFERENCES taxon_groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE index_groups_taxon_groups
  IS 'A calculated index of relationships between indexed taxon groups and the overlapping recording groups.';
COMMENT ON COLUMN index_groups_taxon_groups.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN index_groups_taxon_groups.group_id IS 'Identifies the group_id that overlaps the taxon group. Foreign key to the groups table.';
COMMENT ON COLUMN index_groups_taxon_groups.taxon_group_id IS 'Identifies the taxon group which the group overlaps. Foreign key to the taxon_groups table.';

-- Index: fki_index_groups_taxon_groups_group

-- DROP INDEX fki_index_groups_taxon_groups_group;

CREATE INDEX fki_index_groups_taxon_groups_group
  ON index_groups_taxon_groups
  USING btree
  (group_id);

-- Index: fki_index_groups_taxon_groups_taxon_group

-- DROP INDEX fki_index_groups_taxon_groups_taxon_group;

CREATE INDEX fki_index_groups_taxon_groups_taxon_group
  ON index_groups_taxon_groups
  USING btree
  (taxon_group_id );

