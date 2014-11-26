-- Table: index_groups_locations

-- DROP TABLE index_groups_locations;

CREATE TABLE index_groups_locations
(
  id serial NOT NULL, -- Primary key and unique identifier for the table.
  group_id integer NOT NULL, -- Identifies the recording group that overlaps the location. Foreign key to the groups table.
  location_id integer NOT NULL, -- Identifies the location which the group overlaps. Foreign key to the locations table.
  CONSTRAINT pk_index_groups_locations PRIMARY KEY (id ),
  CONSTRAINT fk_index_groups_locations_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_index_groups_locations_location FOREIGN KEY (location_id)
      REFERENCES locations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE index_groups_locations
  IS 'A calculated index of relationships between indexed locations and the overlapping recording groups.';
COMMENT ON COLUMN index_groups_locations.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN index_groups_locations.group_id IS 'Identifies the group_id that overlaps the location. Foreign key to the groups table.';
COMMENT ON COLUMN index_groups_locations.location_id IS 'Identifies the location which the group overlaps. Foreign key to the locations table.';

-- Index: fki_index_groups_locations_group

-- DROP INDEX fki_index_groups_locations_group;

CREATE INDEX fki_index_groups_locations_group
  ON index_groups_locations
  USING btree
  (group_id);

-- Index: fki_index_groups_locations_location

-- DROP INDEX fki_index_groups_locations_location;

CREATE INDEX fki_index_groups_locations_location
  ON index_groups_locations
  USING btree
  (location_id );

