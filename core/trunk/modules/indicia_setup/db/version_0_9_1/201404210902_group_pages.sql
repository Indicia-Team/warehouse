CREATE TABLE group_pages
(
  id serial NOT NULL, -- Unique identifier and primary key for the table.
  group_id integer, -- Foreign key to the groups table. Identifies the group of which the user is a member.
  caption character varying NOT NULL, -- Caption shown for this form when viewed in the group.
  path character varying NOT NULL, -- Path to the page on the client site which is enabled for this group.
  administrator boolean NOT NULL DEFAULT false, -- Set to true for pages that require group admin rights to be able to see them.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_group_pages PRIMARY KEY (id ),
  CONSTRAINT fk_group_pages_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_group_pages_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_group_pages_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
  
COMMENT ON TABLE group_pages
  IS 'Identifies the list of pages on a client website that are explicitly linked to a group.';
COMMENT ON COLUMN group_pages.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN group_pages.group_id IS 'Foreign key to the groups table. Identifies the group of which the user is a member.';
COMMENT ON COLUMN group_pages.caption IS 'Caption shown for this form when viewed in the group.';
COMMENT ON COLUMN group_pages.path IS 'Path to the page on the client site which is enabled for this group';
COMMENT ON COLUMN group_pages.administrator IS 'Set to true for pages that require group admin rights to be able to see them.';
COMMENT ON COLUMN group_pages.created_on IS 'Date this record was created.';
COMMENT ON COLUMN group_pages.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN group_pages.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN group_pages.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN group_pages.deleted IS 'Has this record been deleted?';

-- Index: idx_groups_pages_path_unique

-- DROP INDEX idx_groups_pages_path_unique;

CREATE UNIQUE INDEX idx_groups_pages_path_unique
  ON group_pages
  USING btree
  (group_id , path )
  WHERE deleted = false;

