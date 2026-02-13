CREATE TABLE IF NOT EXISTS locations_scratchpad_lists (
  id serial,
  location_id integer NOT NULL,
  scratchpad_list_id integer NOT NULL,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer,
  deleted boolean NOT NULL DEFAULT false,
  PRIMARY KEY (id),
  CONSTRAINT fk_locations_scratchpad_lists_location FOREIGN KEY (location_id)
      REFERENCES locations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_locations_scratchpad_lists_scratchpad_list FOREIGN KEY (scratchpad_list_id)
      REFERENCES scratchpad_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_locations_scratchpad_lists_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_locations_scratchpad_lists_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE locations_scratchpad_lists IS 'Join table that links locations to scratchpad lists, e.g. to define a species list associated with a site.';
COMMENT ON COLUMN locations_scratchpad_lists.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN locations_scratchpad_lists.location_id IS 'Location which is associated with the scratchpad list. Foreign key to the locations table.';
COMMENT ON COLUMN locations_scratchpad_lists.scratchpad_list_id IS 'Scratchpad list which is associated with the location. Foreign key to the scratchpad_lists table.';
COMMENT ON COLUMN locations_scratchpad_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN locations_scratchpad_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN locations_scratchpad_lists.created_on IS 'Date this record was updated.';
COMMENT ON COLUMN locations_scratchpad_lists.created_by_id IS 'Foreign key to the users table (updator).';
COMMENT ON COLUMN locations_scratchpad_lists.deleted IS 'Has this record been deleted?';