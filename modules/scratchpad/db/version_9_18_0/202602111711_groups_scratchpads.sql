CREATE TABLE IF NOT EXISTS groups_scratchpad_lists (
  id serial,
  group_id integer NOT NULL,
  scratchpad_list_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer,
  deleted boolean NOT NULL DEFAULT false,
  PRIMARY KEY (id),
  CONSTRAINT fk_groups_scratchpad_lists_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_groups_scratchpad_lists_scratchpad_list FOREIGN KEY (scratchpad_list_id)
      REFERENCES scratchpad_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_groups_scratchpad_lists_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_group_sscratchpad_lists_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE groups_scratchpad_lists IS 'Join table that links groups to scratchpad lists, e.g. to define a species list associated with a project or activity.';
COMMENT ON COLUMN groups_scratchpad_lists.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN groups_scratchpad_lists.group_id IS 'Group which is associated with the scratchpad list. Foreign key to the groups table.';
COMMENT ON COLUMN groups_scratchpad_lists.scratchpad_list_id IS 'Scratchpad list which is associated with the group. Foreign key to the scratchpad_lists table.';
COMMENT ON COLUMN groups_scratchpad_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN groups_scratchpad_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN groups_scratchpad_lists.created_on IS 'Date this record was updated.';
COMMENT ON COLUMN groups_scratchpad_lists.created_by_id IS 'Foreign key to the users table (updator).';
COMMENT ON COLUMN groups_scratchpad_lists.deleted IS 'Has this record been deleted?';