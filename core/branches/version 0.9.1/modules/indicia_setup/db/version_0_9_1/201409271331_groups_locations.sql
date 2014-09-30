CREATE TABLE groups_locations
(
  id serial NOT NULL, -- Unique identifier and primary key for the table.
  group_id integer, -- Foreign key to the groups table. Identifies the group which this location is linked to.
  location_id integer, --Foreign key to the locations table. Identifies the location used by the group.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_groups_locations PRIMARY KEY (id ),
  CONSTRAINT fk_groups_locations_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_groups_locations_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_groups_locations_location FOREIGN KEY (location_id)
      REFERENCES locations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE groups_locations IS 'Join table linking groups to locations used by the group.';
COMMENT ON COLUMN groups_locations.id IS 'Unique identifier and primary key for the table.';
COMMENT ON COLUMN groups_locations.group_id IS 'Foreign key to the groups table. Identifies the group which this location is linked to.';
COMMENT ON COLUMN groups_locations.location_id IS 'Foreign key to the locations table. Identifies the location used by the group.';
COMMENT ON COLUMN groups_locations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN groups_locations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN groups_locations.deleted IS 'Has this record been deleted?';

CREATE INDEX fki_groups_locations_group ON groups_locations(group_id);
CREATE INDEX fki_groups_locations_location ON groups_locations(location_id);
