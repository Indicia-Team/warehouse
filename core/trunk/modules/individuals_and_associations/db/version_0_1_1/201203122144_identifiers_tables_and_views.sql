-- This file contains the DDL for the identifiers table, the identifiers_subject_observations table and related views
-- It must be run after the creation of the known_subjects tables and before the creation of the identifiers_attributes tables

-- SET search_path TO ind01,public;

-- DROP TABLE identifiers CASCADE;

-- Tables

-- Table: identifiers

-- DROP TABLE identifiers;

CREATE TABLE identifiers
(
  id serial NOT NULL,
  issue_authority_id integer, -- termlist value for the organisation which manages this identifier scheme.
  issue_scheme_id integer, -- termlist value for this identifier scheme.
  issue_date timestamp without time zone, -- Date this identifier was issued for use.
  first_use_date timestamp without time zone, -- Date this identifier was first used to identify a known subject.
  last_observed_date timestamp without time zone, -- Date this identifier was most recently recorded.
  final_date timestamp without time zone, -- Date this identifier was known to be no longer marking a live subject.
  identifier_type_id integer NOT NULL, -- termlist value for this type of identifier.
  code varchar(50), -- A unique encoding of the identifier characteristics serialised as a string.
  summary varchar(50), -- Brief summary of the unique identifier.
  status character(1) DEFAULT 'U'::bpchar,
  verified_by_id integer,
  verified_on timestamp without time zone,
  known_subject_id integer, -- Foreign key to the known_subjects table. Identifies the known_subject this identifier belongs to if in use.
  website_id integer NOT NULL, -- Foreign key to the websites table. Website that this identifier record is linked to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_identifiers PRIMARY KEY (id),
  CONSTRAINT fk_identifier_issue_authority FOREIGN KEY (issue_authority_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_issue_scheme FOREIGN KEY (issue_scheme_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_identifier_type FOREIGN KEY (identifier_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT identifiers_status_check CHECK (status = ANY (ARRAY['M'::bpchar, 'I'::bpchar, 'A'::bpchar, 'R'::bpchar, 'U'::bpchar])),
  CONSTRAINT fk_identifier_verifier FOREIGN KEY (verified_by_id)
      REFERENCES ind01.users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_known_subject FOREIGN KEY (known_subject_id)
      REFERENCES known_subjects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifier_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE identifiers OWNER TO indicia_user;
COMMENT ON TABLE identifiers IS 'List of identifiers known to the system. These are unique natural or artificial identifying marks which can be used to make a subject identifiable in subsequent observation';
COMMENT ON COLUMN identifiers.issue_authority_id IS 'Termlist value for the organisation which manages this identifier scheme.';
COMMENT ON COLUMN identifiers.issue_scheme_id IS 'Termlist value for this identifier scheme.';
COMMENT ON COLUMN identifiers.issue_date IS 'Date this identifier was issued for use.';
COMMENT ON COLUMN identifiers.first_use_date IS 'Date this identifier was first used to identify a known subject.';
COMMENT ON COLUMN identifiers.last_observed_date IS 'Date this identifier was most recently recorded.';
COMMENT ON COLUMN identifiers.final_date IS 'Date this identifier was known to be no longer marking a live subject.';
COMMENT ON COLUMN identifiers.identifier_type_id IS 'Termlist value for this type of identifier.';
COMMENT ON COLUMN identifiers.code IS 'A unique encoding of the identifier characteristics serialised as a string.';
COMMENT ON COLUMN identifiers.summary IS 'Brief summary of the unique identifier.';
COMMENT ON COLUMN identifiers.status IS 'Status of this identifier. M - manufactured, I - issued, A - attached, R - retired, U - unknown.';
COMMENT ON COLUMN identifiers.verified_by_id IS 'Foreign key to the users table (verifier).';
COMMENT ON COLUMN identifiers.verified_on IS 'Date this identifier was verified and status changed from U to A or R.';
COMMENT ON COLUMN identifiers.known_subject_id IS 'Foreign key to the known_subjects table. Identifies the known_subject this identifier belongs to if in use.';
COMMENT ON COLUMN identifiers.website_id IS 'Foreign key to the websites table. Identifies the website that this identifiers is recorded on.';
COMMENT ON COLUMN identifiers.created_on IS 'Date this record was created.';
COMMENT ON COLUMN identifiers.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN identifiers.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN identifiers.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN identifiers.deleted IS 'Has this record been deleted?';

-- Index: fki_identifiers_issue_authorities

-- DROP INDEX fki_identifiers_issue_authorities;

CREATE INDEX fki_identifiers_issue_authorities
  ON identifiers
  USING btree
  (issue_authority_id);

-- Index: fki_identifiers_issue_schemes

-- DROP INDEX fki_identifiers_issue_schemes;

CREATE INDEX fki_identifiers_issue_schemes
  ON identifiers
  USING btree
  (issue_scheme_id);

-- Index: fki_identifiers_identifier_types

-- DROP INDEX fki_identifiers_identifier_types;

CREATE INDEX fki_identifiers_identifier_types
  ON identifiers
  USING btree
  (identifier_type_id);

-- Index: fki_identifiers_codes

-- DROP INDEX fki_identifiers_codes;

CREATE INDEX fki_identifiers_codes
  ON identifiers
  USING btree
  (code);

-- Index: fki_identifiers_known_subjects

-- DROP INDEX fki_identifiers_known_subjects;

CREATE INDEX fki_identifiers_known_subjects
  ON identifiers
  USING btree
  (known_subject_id);

-- Index: fki_identifiers_websites

-- DROP INDEX fki_identifiers_websites;

CREATE INDEX fki_identifiers_websites
  ON identifiers
  USING btree
  (website_id);

-- Link tables

-- Table: identifiers_subject_observations

-- DROP TABLE identifiers_subject_observations;

CREATE TABLE identifiers_subject_observations
(
  id serial NOT NULL,
  identifier_id integer NOT NULL, -- Foreign key to the identifiers table. Identifies the subject_observation that is identified by the subject_observation_id.
  subject_observation_id integer NOT NULL, -- Foreign key to the subject_observations table. Identifies the occurence that the subject_observation is linked to in order to determine its taxon.
  matched boolean NOT NULL DEFAULT false, -- Whether this observation matches a known identifier, true if a match exists.
  verified_status character(1) DEFAULT 'U'::bpchar, -- Status of this identifier observation. M - misread, V - verified, U - unknown.
  verified_by_id integer, -- Foreign key to the users table (verifier).
  verified_on timestamp without time zone, -- Date this identifier observation was verified and status changed from U to M or V.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_identifiers_subject_observations PRIMARY KEY (id),
  CONSTRAINT fk_identifiers_subject_observations_identifiers FOREIGN KEY (identifier_id)
      REFERENCES identifiers (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_identifiers_subject_observations_subject_observations FOREIGN KEY (subject_observation_id)
      REFERENCES subject_observations (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT s_subject_observations_verified_status_check CHECK (verified_status = ANY (ARRAY['M'::bpchar, 'I'::bpchar, 'A'::bpchar, 'R'::bpchar, 'U'::bpchar])),
  CONSTRAINT fk_identifiers_subject_observations_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_s_subject_observations_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE identifiers_subject_observations OWNER TO indicia_user;
COMMENT ON TABLE identifiers_subject_observations IS 'Join table which links identifiers with subject_observations.';
COMMENT ON COLUMN identifiers_subject_observations.identifier_id IS 'Foreign key to the identifiers table. Identifies the subject_observation that is identified by the subject_observation_id.';
COMMENT ON COLUMN identifiers_subject_observations.subject_observation_id IS 'Foreign key to the subject_observations table. Identifies the occurence that the subject_observation is linked to in order to determine its taxon.';
COMMENT ON COLUMN identifiers_subject_observations.matched IS 'Whether this observation matches a known identifier, true if a match exists.';
COMMENT ON COLUMN identifiers_subject_observations.verified_status IS 'Status of this identifier observation. M - misread, V - verified, U - unknown.';
COMMENT ON COLUMN identifiers_subject_observations.verified_by_id IS 'Foreign key to the users table (verifier).';
COMMENT ON COLUMN identifiers_subject_observations.verified_on IS 'Date this identifier observation was verified and status changed from U to M or V.';
COMMENT ON COLUMN identifiers_subject_observations.created_on IS 'Date this record was created.';
COMMENT ON COLUMN identifiers_subject_observations.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN identifiers.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN identifiers.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN identifiers_subject_observations.deleted IS 'Has this record been deleted?';


-- Index: fki_identifiers_subject_observations_identifier

-- DROP INDEX fki_identifiers_subject_observations_identifier;

CREATE INDEX fki_identifiers_subject_observations_identifier
  ON identifiers_subject_observations
  USING btree
  (identifier_id);

-- Index: fki_identifiers_subject_observations_subject_observation

-- DROP INDEX fki_identifiers_subject_observations_subject_observation;

CREATE INDEX fki_identifiers_subject_observations_subject_observation
  ON identifiers_subject_observations
  USING btree
  (subject_observation_id);

-- Views

-- View: list_identifiers

-- DROP VIEW list_identifiers;

CREATE OR REPLACE VIEW list_identifiers AS 
 SELECT i.id, i.first_use_date, t_t.term AS identifier_type, i.status, i.code, i.summary, i.known_subject_id, substring(ks.description from 1 for 30) as short_description, i.website_id, i.deleted
   FROM identifiers i
   LEFT JOIN known_subjects ks ON i.known_subject_id = ks.id
   LEFT JOIN termlists_terms t_tlt ON i.identifier_type_id = t_tlt.id
   LEFT JOIN terms t_t ON t_tlt.term_id = t_t.id
   LEFT JOIN websites w ON w.id = i.website_id
  WHERE i.deleted = false;

-- ALTER TABLE list_identifiers OWNER TO indicia_user;

-- View: detail_identifiers

-- DROP VIEW detail_identifiers;

CREATE OR REPLACE VIEW detail_identifiers AS 
 SELECT i.id, i.issue_authority_id, a_t.term AS issue_authority, i.issue_scheme_id, s_t.term AS issue_scheme, i.issue_date, i.first_use_date,  i.last_observed_date, i.final_date, i.identifier_type_id, t_t.term AS identifier_type, i.status, i.code, i.summary, i.known_subject_id, ks.description, substring(ks.description from 1 for 30) as short_description, i.website_id, w.title AS website, i.created_by_id, c.username AS created_by, i.created_on, i.updated_by_id, u.username AS updated_by, i.updated_on, i.deleted
   FROM identifiers i
   LEFT JOIN known_subjects ks ON i.known_subject_id = ks.id
   LEFT JOIN termlists_terms a_tlt ON i.issue_authority_id = a_tlt.id
   LEFT JOIN terms a_t ON a_tlt.term_id = a_t.id
   LEFT JOIN termlists_terms s_tlt ON i.issue_scheme_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
   LEFT JOIN termlists_terms t_tlt ON i.identifier_type_id = t_tlt.id
   LEFT JOIN terms t_t ON t_tlt.term_id = t_t.id
   LEFT JOIN websites w ON w.id = i.website_id
   JOIN users c ON c.id = i.created_by_id
   JOIN users u ON u.id = i.updated_by_id
  WHERE i.deleted = false;

-- ALTER TABLE detail_identifiers OWNER TO indicia_user;

-- View: gv_identifiers

-- DROP VIEW gv_identifiers;

CREATE OR REPLACE VIEW gv_identifiers AS 
 SELECT i.id, a_t.term AS issue_authority, s_t.term AS issue_scheme, i.issue_date, i.first_use_date,  i.last_observed_date, i.final_date, t_t.term AS identifier_type, i.status, i.code, i.summary, substring(ks.description from 1 for 30) as short_description, i.website_id, w.title AS website, i.deleted
   FROM identifiers i
   LEFT JOIN known_subjects ks ON i.known_subject_id = ks.id
   LEFT JOIN termlists_terms a_tlt ON i.issue_authority_id = a_tlt.id
   LEFT JOIN terms a_t ON a_tlt.term_id = a_t.id
   LEFT JOIN termlists_terms s_tlt ON i.issue_scheme_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
   LEFT JOIN termlists_terms t_tlt ON i.identifier_type_id = t_tlt.id
   LEFT JOIN terms t_t ON t_tlt.term_id = t_t.id
   LEFT JOIN websites w ON w.id = i.website_id
  WHERE i.deleted = false;

-- ALTER TABLE gv_identifiers OWNER TO indicia_user;



