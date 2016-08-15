
INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat pass definitions', 'Definitions of types of bat pass for Ecobat.', now(), 1, now(), 1, 'ecobat:pass_definitions');

select insert_term('Registration 15s', 'eng', 1, null, 'ecobat:pass_definitions');
select insert_term('Pass 1s gap', 'eng', 2, null, 'ecobat:pass_definitions');
select insert_term('Pass 2s gap', 'eng', 3, null, 'ecobat:pass_definitions');
select insert_term('Pulses', 'eng', 4, null, 'ecobat:pass_definitions');
select insert_term('Other', 'eng', 5, null, 'ecobat:pass_definitions');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat detector manufacturers', 'List of bat detector makes.', now(), 1, now(), 1, 'ecobat:detector_makes');

select insert_term('Batbox', 'eng', 1, null, 'ecobat:detector_makes');
select insert_term('Ciel', 'eng', 2, null, 'ecobat:detector_makes');
select insert_term('Courtpan', 'eng', 3, null, 'ecobat:detector_makes');
select insert_term('Elekon', 'eng', 4, null, 'ecobat:detector_makes');
select insert_term('Magenta', 'eng', 5, null, 'ecobat:detector_makes');
select insert_term('Peersonic', 'eng', 6, null, 'ecobat:detector_makes');
select insert_term('Pettersson', 'eng', 7, null, 'ecobat:detector_makes');
select insert_term('Titley Scientific', 'eng', 8, null, 'ecobat:detector_makes');
select insert_term('Wildlife Acoustics', 'eng', 9, null, 'ecobat:detector_makes');
select insert_term('Other', 'eng', 10, null, 'ecobat:detector_makes');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat linear feature', 'List of linear features for Ecobat static detector records.', now(), 1, now(), 1, 'ecobat:linear_features');

select insert_term('None', 'eng', 1, null, 'ecobat:linear_features');
select insert_term('Ditch', 'eng', 2, null, 'ecobat:linear_features');
select insert_term('Hedgerow', 'eng', 3, null, 'ecobat:linear_features');
select insert_term('Running water', 'eng', 4, null, 'ecobat:linear_features');
select insert_term('Standing water', 'eng', 5, null, 'ecobat:linear_features');
select insert_term('Treeline', 'eng', 6, null, 'ecobat:linear_features');
select insert_term('Woodland edge', 'eng', 7, null, 'ecobat:linear_features');
select insert_term('Other', 'eng', 8, null, 'ecobat:linear_features');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat anthropogenic feature', 'List of man-made features for Ecobat static detector records.', now(), 1, now(), 1, 'ecobat:anthropogenic_features');

select insert_term('None', 'eng', 1, null, 'ecobat:anthropogenic_features');
select insert_term('Building', 'eng', 2, null, 'ecobat:anthropogenic_features');
select insert_term('Fenceline', 'eng', 3, null, 'ecobat:anthropogenic_features');
select insert_term('Major road', 'eng', 4, null, 'ecobat:anthropogenic_features');
select insert_term('Minor road', 'eng', 5, null, 'ecobat:anthropogenic_features');
select insert_term('Streetlight', 'eng', 6, null, 'ecobat:anthropogenic_features');
select insert_term('Wind turbine', 'eng', 7, null, 'ecobat:anthropogenic_features');
select insert_term('Other', 'eng', 8, null, 'ecobat:anthropogenic_features');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat rainfall', 'List of rainfall terms.', now(), 1, now(), 1, 'ecobat:rainfall');

select insert_term('Dry', 'eng', 1, null, 'ecobat:rainfall');
select insert_term('Drizzle', 'eng', 2, null, 'ecobat:rainfall');
select insert_term('Heavy', 'eng', 3, null, 'ecobat:rainfall');
select insert_term('Other', 'eng', 4, null, 'ecobat:rainfall');

INSERT INTO termlists (title, description, created_on, created_by_id, updated_on, updated_by_id, external_key)
VALUES ('Ecobat wind speed units', 'List of units for measuring wind speed.', now(), 1, now(), 1, 'ecobat:wind_speed_units');

select insert_term('Beaufort', 'eng', 1, null, 'ecobat:wind_speed_units');
select insert_term('Mph', 'eng', 2, null, 'ecobat:wind_speed_units');
select insert_term('Other', 'eng', 3, null, 'ecobat:wind_speed_units');

CREATE TABLE ecobat_occurrences
(
  id serial NOT NULL,
  taxa_taxon_list_id integer NOT NULL,
  external_key char(16), --
  entered_sref character varying(40) NOT NULL,
  entered_sref_system character varying (10) NOT NULL,
  easting integer, --
  northing integer, --
  geom geometry(Geometry,900913),
  map_sq_10km_id integer, --
  sensitivity integer NOT NULL default 1,
  date_start date NOT NULL,
  day_of_year integer NOT NULL,
  passes integer NOT NULL,
  pass_definition_id integer NOT NULL,
  detector_make_id integer,
  detector_make_other character varying,
  detector_model character varying NOT NULL,
  detector_height_m numeric(4,2),
  roost_within_25m boolean NOT NULL DEFAULT FALSE,
  activity_elevated_by_roost boolean NOT NULL DEFAULT FALSE,
  roost_species character varying,
<<<<<<< HEAD
  linear_feature_adjacent_id integer,
  linear_feature_25m_id integer,
  anthropogenic_feature_adjacent_id integer,
  anthropogenic_feature_25m_id integer,
  temperature_c numeric(4,2),
  rainfall_id integer,
  wind_speed_mph integer,
=======
  linear_feature_adjacent_id integer NOT NULL,
  linear_feature_25m_id integer NOT NULL,
  anthropogenic_feature_adjacent_id integer NOT NULL,
  anthropogenic_feature_25m_id integer NOT NULL,
  temperature_c numeric(4,2),
  rainfall_id integer,
  wind_speed integer,
  wind_speed_unit_id integer,
>>>>>>> 2c86b3335173e0a2d929e82cb3982400a2ba5fa1
  notes character varying,
  occurrence_id integer,
  group_id integer,
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL,
  import_guid character varying,
  CONSTRAINT pk_ecobat_occurrences PRIMARY KEY (id),
  CONSTRAINT fk_ecobat_occurrence_10km_map_square FOREIGN KEY (map_sq_10km_id)
      REFERENCES map_squares (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_taxon FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_pass_definition FOREIGN KEY (pass_definition_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_detector_make FOREIGN KEY (detector_make_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_linear_feature_adjacent FOREIGN KEY (linear_feature_adjacent_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_linear_feature_25m FOREIGN KEY (linear_feature_25m_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_anthropogenic_feature_adjacent FOREIGN KEY (linear_feature_adjacent_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_anthropogenic_feature_25m FOREIGN KEY (linear_feature_25m_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_rainfall_id FOREIGN KEY (rainfall_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_wind_speed_unit FOREIGN KEY (wind_speed_unit_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_occurrences FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_ecobat_occurrence_group FOREIGN KEY (group_id)
      REFERENCES groups (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT chk_ecobat_sensitivity
      CHECK (sensitivity is null or sensitivity in (1, 2, 3))
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE ecobat_occurrences
  IS 'Optimised table for ecobat reference range occurrences.';

COMMENT ON COLUMN ecobat_occurrences.id IS 'Unique identifier of the reference range record.';
COMMENT ON COLUMN ecobat_occurrences.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxa on a taxon list that this occurrence is a record of.';
COMMENT ON COLUMN ecobat_occurrences.external_key IS 'Preferred taxon version key.';
COMMENT ON COLUMN ecobat_occurrences.entered_sref IS 'Spatial reference that was provided for the record.';
COMMENT ON COLUMN ecobat_occurrences.entered_sref_system IS 'System that was used for the spatial reference in entered_sref.';
COMMENT ON COLUMN ecobat_occurrences.easting IS 'OSGB easting for the record.';
COMMENT ON COLUMN ecobat_occurrences.northing IS 'OSGB northing for the record.';
COMMENT ON COLUMN ecobat_occurrences.geom IS 'Geometry for the record.';
COMMENT ON COLUMN ecobat_occurrences.map_sq_10km_id IS 'Foreign key to the map_squares table. Identifies the 10km square the record falls into.';
COMMENT ON COLUMN ecobat_occurrences.sensitivity IS 'Sensitivity preferences for the record. 1=open, 2=10km blur, 3=open.';
COMMENT ON COLUMN ecobat_occurrences.date_start IS 'Date at the start of the nights surveying.';
COMMENT ON COLUMN ecobat_occurrences.passes IS 'Total number of passes during the night for this species.';
COMMENT ON COLUMN ecobat_occurrences.pass_definition_id IS 'Foreign key to the termlists_terms table. Defines the method used to identify a pass.';
COMMENT ON COLUMN ecobat_occurrences.detector_make_id IS 'The makeof bat detector used, picked from a controlled list.';
COMMENT ON COLUMN ecobat_occurrences.detector_make_other IS 'The make of bat detector used if not on the list.';
COMMENT ON COLUMN ecobat_occurrences.detector_model IS 'The model of bat detector used.';
COMMENT ON COLUMN ecobat_occurrences.detector_height_m IS 'Height of the detector from the ground in metres.';
COMMENT ON COLUMN ecobat_occurrences.roost_within_25m IS 'Presence or absence of a roost within 25m.';
COMMENT ON COLUMN ecobat_occurrences.activity_elevated_by_roost IS 'Flag set if activity was elevated because of the presence of a roost.';
COMMENT ON COLUMN ecobat_occurrences.roost_species IS 'Free text list of species at the roost(s).';
COMMENT ON COLUMN ecobat_occurrences.linear_feature_adjacent_id IS 'Type of linear feature adjacent to the detector.';
COMMENT ON COLUMN ecobat_occurrences.linear_feature_25m_id IS 'Type of linear feature within 25m of the detector.';
COMMENT ON COLUMN ecobat_occurrences.anthropogenic_feature_adjacent_id IS 'Type of anthropogenic feature adjacent to the detector.';
COMMENT ON COLUMN ecobat_occurrences.anthropogenic_feature_25m_id IS 'Type of anthropogenic feature within 25m of the detector.';
COMMENT ON COLUMN ecobat_occurrences.temperature_c IS 'Temperature at sunset (degrees centigrade)';
COMMENT ON COLUMN ecobat_occurrences.rainfall_id IS 'Type of rainfall at sunset';
COMMENT ON COLUMN ecobat_occurrences.wind_speed IS 'Wind speed at sunset in the unit defined by wind_speed_unit_id';
COMMENT ON COLUMN ecobat_occurrences.wind_speed_unit_id IS 'Unit used for the wind speed measurement.';
COMMENT ON COLUMN ecobat_occurrences.occurrence_id IS 'Foreign key to the occurrences table. Identifies the occurrence lodged in the main occurrences table for reference range records which are made publically available.';
COMMENT ON COLUMN ecobat_occurrences.group_id IS 'Foreign key to the groups table. Identifies the Consultants Portal project the record belongs to  .';
COMMENT ON COLUMN ecobat_occurrences.created_on IS 'Date this record was created.';
COMMENT ON COLUMN ecobat_occurrences.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN ecobat_occurrences.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN ecobat_occurrences.updated_by_id IS 'Foreign key to the users table (last updater).';