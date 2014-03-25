-- This file contains the DDL for the subject_observations table, occurrences_subject_observations table and related views
-- It must be run after the creation of the known_subjects tables and before the creation of the subject_observations_attributes tables

-- SET search_path TO ind01,public;

-- DROP TABLE subject_observations CASCADE;
-- DROP TABLE occurrences_subject_observations;

-- Tables

-- Table: subject_observations

-- DROP TABLE subject_observations;

CREATE TABLE subject_observations
(
  id serial NOT NULL,
  parent_id integer, -- In cases where subject_observations form a compositional hierarchy, this allows subject_observations to be linked to a parent subject_observation. For example, a subject_observation mixed flock will have several single-species subject_observations within it. They in turn may contain family parties.
  sample_id integer NOT NULL, -- Foreign key to the samples table. Identifies the sample that this subject_observation belongs to.
  subject_type_id integer NOT NULL, -- Identifies term describing the type of the subject in this observation. Foreign key to the termlists_terms table.
  known_subject_id integer, -- Foreign key to the known_subjects table. Identifies the known_subject this subject_observation is an observation of, if the subject is known.
  "count" integer, -- The number of individuals in the subject group observed, if counted.
  count_qualifier_id integer, -- Identifies term describing the precision of the count of the subject in this observation. Foreign key to the termlists_terms table.
  "comment" text, -- Comment regarding the subject_observation.
  website_id integer NOT NULL, -- Foreign key to the websites table. Website that this subject_observation record is linked to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_subject_observations PRIMARY KEY (id),
  CONSTRAINT fk_subject_observations_parent FOREIGN KEY (parent_id)
      REFERENCES subject_observations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observations_samples FOREIGN KEY (sample_id)
      REFERENCES samples (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_subject_type FOREIGN KEY (subject_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_known_subject FOREIGN KEY (known_subject_id)
      REFERENCES known_subjects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_count_qualifier FOREIGN KEY (count_qualifier_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_subject_observation_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE subject_observations OWNER TO indicia_user;
COMMENT ON TABLE subject_observations IS 'List of subject_observations known to the system. These are one time observations of groups or individuals';
COMMENT ON COLUMN subject_observations.parent_id IS 'In cases where subject_observations form a compositional hierarchy, this allows subject_observations to be linked to a parent subject_observation. For example, a subject_observation mixed flock will have several single-species subject_observations within it. They in turn may contain family parties.';
COMMENT ON COLUMN subject_observations.sample_id IS 'Foreign key to the samples table. Identifies the sample that this subject_observation belongs to.';
COMMENT ON COLUMN subject_observations.subject_type_id IS 'Identifies term describing the type of the subject in this observation. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN subject_observations.known_subject_id IS 'Foreign key to the known_subjects table. Identifies the known_subject this subject_observation is an observation of, if the subject is known.';
COMMENT ON COLUMN subject_observations."count" IS 'The number of individuals in the subject group observed, if counted.';
COMMENT ON COLUMN subject_observations.count_qualifier_id IS 'Identifies term describing the precision of the count of the subject in this observation. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN subject_observations."comment" IS 'Comment regarding the subject_observation.';
COMMENT ON COLUMN subject_observations.website_id IS 'Foreign key to the websites table. Identifies the website that this subject_observations is recorded on.';
COMMENT ON COLUMN subject_observations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN subject_observations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN subject_observations.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN subject_observations.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN subject_observations.deleted IS 'Has this record been deleted?';


-- Index: fki_subject_observations_subject_observations

-- DROP INDEX fki_subject_observations_subject_observations;

CREATE INDEX fki_subject_observations_subject_observations
  ON subject_observations
  USING btree
  (parent_id);

-- Index: fki_subject_observations_samples

-- DROP INDEX fki_subject_observations_samples;

CREATE INDEX fki_subject_observations_samples
  ON subject_observations
  USING btree
  (sample_id);

-- Index: fki_subject_observations_subject_types

-- DROP INDEX fki_subject_observations_subject_types;

CREATE INDEX fki_subject_observations_subject_types
  ON subject_observations
  USING btree
  (subject_type_id);

-- Index: fki_subject_observations_known_subjects

-- DROP INDEX fki_subject_observations_known_subjects;

CREATE INDEX fki_subject_observations_known_subjects
  ON subject_observations
  USING btree
  (known_subject_id);

-- Index: fki_subject_observations_websites

-- DROP INDEX fki_subject_observations_websites;

CREATE INDEX fki_subject_observations_websites
  ON subject_observations
  USING btree
  (website_id);

-- Link tables

-- Table: occurrences_subject_observations

-- DROP TABLE occurrences_subject_observations;

CREATE TABLE occurrences_subject_observations
(
  id serial NOT NULL,
  occurrence_id integer NOT NULL, -- Foreign key to the occurrences table. Identifies the subject_observation that is identified by the subject_observation_id.
  subject_observation_id integer NOT NULL, -- Foreign key to the subject_observations table. Identifies the occurence that the subject_observation is linked to in order to determine its taxon.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_occurrences_subject_observations PRIMARY KEY (id),
  CONSTRAINT fk_occurrences_subject_observations_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrences_subject_observations_occurrences FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrences_subject_observations_subject_observations FOREIGN KEY (subject_observation_id)
      REFERENCES subject_observations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE occurrences_subject_observations OWNER TO indicia_user;
COMMENT ON TABLE occurrences_subject_observations IS 'Join table which identifies the taxa which the known subjects belong to.';
COMMENT ON COLUMN occurrences_subject_observations.occurrence_id IS 'Foreign key to the occurrences table. Identifies the subject_observation that is identified by the subject_observation_id.';
COMMENT ON COLUMN occurrences_subject_observations.subject_observation_id IS 'Foreign key to the subject_observations table. Identifies the occurence that the subject_observation is linked to in order to determine its taxon.';
COMMENT ON COLUMN occurrences_subject_observations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN occurrences_subject_observations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrences_subject_observations.deleted IS 'Has this record been deleted?';


-- Index: fki_occurrences_subject_observations_occurrence

-- DROP INDEX fki_occurrences_subject_observations_occurrence;

CREATE INDEX fki_occurrences_subject_observations_occurrence
  ON occurrences_subject_observations
  USING btree
  (occurrence_id);

-- Index: fki_occurrences_subject_observations_subject_observation

-- DROP INDEX fki_occurrences_subject_observations_subject_observation;

CREATE INDEX fki_occurrences_subject_observations_subject_observation
  ON occurrences_subject_observations
  USING btree
  (subject_observation_id);

-- Views

-- View: list_subject_observations

-- DROP VIEW list_subject_observations;

CREATE OR REPLACE VIEW list_subject_observations AS 
 SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, array_to_string(array_agg(t.taxon),', ') AS taxa, so.website_id, so.id, s.recorder_names, so.subject_type_id, s_t.term AS subject_type, so."count", substring(so.comment from 1 for 30) as short_comment, so.sample_id, s.survey_id
   FROM subject_observations so
   JOIN samples s ON so.sample_id = s.id AND s.deleted = false
   LEFT JOIN locations l ON s.location_id = l.id
   LEFT JOIN occurrences_subject_observations oso ON so.id = oso.subject_observation_id
   LEFT JOIN occurrences occ ON occ.id = oso.occurrence_id
   LEFT JOIN taxa_taxon_lists ttl ON occ.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
   LEFT JOIN termlists_terms s_tlt ON so.subject_type_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
  WHERE so.deleted = false
  GROUP BY survey, location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, so.website_id, so.id, s.recorder_names, so.subject_type_id, subject_type, so."count", short_comment, so.sample_id, s.survey_id;

-- ALTER TABLE list_subject_observations OWNER TO indicia_user;

-- View: detail_subject_observations

-- DROP VIEW detail_subject_observations;

CREATE OR REPLACE VIEW detail_subject_observations AS 
 SELECT su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, array_to_string(array_agg(t.taxon),', ') AS taxa, so.website_id, so.id, so.parent_id, s.recorder_names, so.subject_type_id, s_t.term AS subject_type, so.known_subject_id, so."count", so.count_qualifier_id, g_t.term AS count_qualifier, so."comment", substring(so.comment from 1 for 30) as short_comment, array_to_string(array_agg(occ.taxa_taxon_list_id),', ') AS taxa_taxon_list_ids, array_to_string(array_agg(ttl.taxon_meaning_id),', ') AS taxon_meaning_ids, s.geom, st_astext(s.geom) AS wkt, s.location_name, s.survey_id, s.location_id, l.code AS location_code, so.sample_id, so.created_by_id, c.username AS created_by, so.created_on, so.updated_by_id, u.username AS updated_by, so.updated_on, so.deleted
   FROM subject_observations so
   JOIN samples s ON so.sample_id = s.id AND s.deleted = false
   LEFT JOIN locations l ON s.location_id = l.id
   LEFT JOIN occurrences_subject_observations oso ON so.id = oso.subject_observation_id
   LEFT JOIN occurrences occ ON occ.id = oso.occurrence_id
   LEFT JOIN taxa_taxon_lists ttl ON occ.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
   LEFT JOIN termlists_terms s_tlt ON so.subject_type_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
   LEFT JOIN termlists_terms g_tlt ON so.count_qualifier_id = g_tlt.id
   LEFT JOIN terms g_t ON g_tlt.term_id = g_t.id
   JOIN users c ON c.id = so.created_by_id
   JOIN users u ON u.id = so.updated_by_id
  WHERE so.deleted = false
  GROUP BY survey, location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, so.website_id, so.id, so.parent_id, s.recorder_names, so.subject_type_id, subject_type, so.known_subject_id, so."count", so.count_qualifier_id, count_qualifier, so."comment", short_comment, s.geom, wkt, s.location_name, s.survey_id, s.location_id, location_code, so.sample_id, so.created_by_id, created_by, so.created_on, so.updated_by_id, updated_by, so.updated_on, so.deleted;

-- ALTER TABLE detail_subject_observations OWNER TO indicia_user;

-- View: gv_subject_observations

-- DROP VIEW gv_subject_observations;

CREATE OR REPLACE VIEW gv_subject_observations AS 
 SELECT so.id, w.title AS website, s.title AS survey, so.sample_id, array_to_string(array_agg(t.taxon),', ') AS taxa, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, so.subject_type_id, s_t.term AS subject_type, so."count", substring(so.comment from 1 for 30) as short_comment, so.deleted, so.website_id
   FROM subject_observations so
   LEFT JOIN occurrences_subject_observations oso ON so.id = oso.subject_observation_id
   LEFT JOIN occurrences occ ON occ.id = oso.occurrence_id
   LEFT JOIN taxa_taxon_lists ttl ON occ.taxa_taxon_list_id = ttl.id
   JOIN taxa t ON ttl.taxon_id = t.id
   JOIN samples sa ON so.sample_id = sa.id AND sa.deleted = false
   JOIN surveys s ON s.id = sa.survey_id AND s.deleted = false
   LEFT JOIN termlists_terms s_tlt ON so.subject_type_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
   JOIN websites w ON w.id = so.website_id AND w.deleted = false
   LEFT JOIN locations l ON sa.location_id = l.id AND l.deleted = false
  WHERE so.deleted = false
  GROUP BY so.id, website, survey, so.sample_id, sa.date_start, sa.date_end, sa.date_type, sa.entered_sref, sa.entered_sref_system, sa.location_name, l.name, so.subject_type_id, subject_type, so."count", short_comment, so.deleted, so.website_id;

-- ALTER TABLE gv_subject_observations OWNER TO indicia_user;



