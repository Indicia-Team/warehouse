-- Table: taxon_groups_taxon_lists

-- DROP TABLE taxon_groups_taxon_lists;

CREATE TABLE taxon_groups_taxon_lists
(
  id serial NOT NULL,
  taxon_group_id integer NOT NULL,
  taxon_list_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  CONSTRAINT pk_taxon_groups_taxon_lists PRIMARY KEY (id),
  CONSTRAINT fk_taxon_groups_taxon_lists_taxon_groups FOREIGN KEY (taxon_group_id)
      REFERENCES taxon_groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_taxon_groups_taxon_lists_taxon_lists FOREIGN KEY (taxon_list_id)
      REFERENCES taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

-- Index: fki_taxon_groups_taxon_lists_taxon_groups

-- DROP INDEX fki_taxon_groups_taxon_lists_taxon_groups;

CREATE INDEX fki_taxon_groups_taxon_lists_taxon_groups
  ON taxon_groups_taxon_lists
  USING btree
  (taxon_group_id);

-- Index: fki_taxon_groups_taxon_lists_taxon_lists

-- DROP INDEX fki_taxon_groups_taxon_lists_taxon_lists;

CREATE INDEX fki_taxon_groups_taxon_lists_taxon_lists
  ON taxon_groups_taxon_lists
  USING btree
  (taxon_list_id);

COMMENT ON TABLE taxon_groups_taxon_lists IS 'Defines associations between a taxon list and the taxonomic groups it covers.';
COMMENT ON COLUMN taxon_groups_taxon_lists.taxon_group_id IS 'Foreign key to the taxon_groups table - identifies the group associated with the taxon list.';
COMMENT ON COLUMN taxon_groups_taxon_lists.taxon_list_id IS 'Foreign key to the taxon_lists table - identifies the list associated with the taxon group.';
COMMENT ON COLUMN taxon_groups_taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN taxon_groups_taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN taxon_groups_taxon_lists.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN taxon_groups_taxon_lists.updated_by_id IS 'Foreign key to the users table (last updater).';

-- Add an attribute to taxon lists to define if the synching from parent list to child list is automated.
ALTER TABLE taxon_lists ADD synch_groups_from_parent BOOLEAN NOT NULL DEFAULT FALSE;
COMMENT ON COLUMN taxon_lists.synch_groups_from_parent IS 'Flag defining if taxa from selected taxon groups are copied from parent lists down to child lists automatically.';