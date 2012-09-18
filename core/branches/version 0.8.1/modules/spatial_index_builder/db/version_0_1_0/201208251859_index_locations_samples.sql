-- Table: index_locations_samples

-- DROP TABLE index_locations_samples;

CREATE TABLE index_locations_samples
(
  id serial NOT NULL, -- Primary key and unique identifier for the table.
  location_id integer NOT NULL, -- Identifies the location which the sample at least overlaps. Foreign key to the locations table.
  sample_id integer NOT NULL, -- Identifies the sample that at least overlaps the location. Foreign key to the samples table.
  contains boolean NOT NULL, -- Set to true if the sample is contained within the location boundary, or false if there is just an overlap.
  CONSTRAINT pk_index_locations_samples PRIMARY KEY (id),
  CONSTRAINT fk_index_locations_samples_location FOREIGN KEY (location_id)
      REFERENCES locations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_indxe_locations_samples_sample FOREIGN KEY (sample_id)
      REFERENCES samples (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
COMMENT ON TABLE index_locations_samples IS 'A calculated index of relationships between locations and the overlapping or contained samples. Requires the warehouse spatial_index_builder module to be enabled for population. A record exists in this table for each combination of location boundary and sample where there is at least an overlap.';
COMMENT ON COLUMN index_locations_samples.id IS 'Primary key and unique identifier for the table.';
COMMENT ON COLUMN index_locations_samples.location_id IS 'Identifies the location which the sample at least overlaps. Foreign key to the locations table.';
COMMENT ON COLUMN index_locations_samples.sample_id IS 'Identifies the sample that at least overlaps the location. Foreign key to the samples table.';
COMMENT ON COLUMN index_locations_samples.contains IS 'Set to true if the sample is contained within the location boundary, or false if there is just an overlap.';


-- Index: fki_index_locations_samples_location

-- DROP INDEX fki_index_locations_samples_location;

CREATE INDEX fki_index_locations_samples_location
  ON index_locations_samples
  USING btree
  (location_id);

-- Index: fki_indxe_locations_samples_sample

-- DROP INDEX fki_indxe_locations_samples_sample;

CREATE INDEX fki_indxe_locations_samples_sample
  ON index_locations_samples
  USING btree
  (sample_id);

