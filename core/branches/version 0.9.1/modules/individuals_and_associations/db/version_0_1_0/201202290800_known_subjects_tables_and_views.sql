-- This file contains the DDL for the known_subjects table, known_subjects_taxa_taxon_lists table, known_subject_comment table and related views
-- It must be run before the creation of the subject_observations tables and before the creation of the known_subjects_attributes tables

-- SET search_path TO ind01,public;

-- DROP TABLE known_subjects CASCADE;
-- DROP TABLE known_subject_comments;
-- DROP TABLE known_subjects_taxa_taxon_lists;

-- Tables

-- Table: known_subjects

-- DROP TABLE known_subjects;

CREATE TABLE known_subjects
(
  id serial NOT NULL,
  parent_id integer, -- In cases where known_subjects form a compositional hierarchy, this allows known_subjects to be linked to a parent known_subject.
  subject_type_id integer NOT NULL, -- Identifies term describing the type of the subject. Foreign key to the termlists_terms table.
  description text, -- Description of the known subject.
  website_id integer NOT NULL, -- Foreign key to the websites table. Website that this known_subject record is linked to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  updated_on timestamp without time zone NOT NULL, -- Date this record was last updated.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (last updater).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_known_subjects PRIMARY KEY (id),
  CONSTRAINT fk_known_subjects_parent FOREIGN KEY (parent_id)
      REFERENCES known_subjects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_subject_type FOREIGN KEY (subject_type_id)
      REFERENCES termlists_terms (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_website FOREIGN KEY (website_id)
      REFERENCES websites (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE known_subjects OWNER TO indicia_user;
COMMENT ON TABLE known_subjects IS 'List of known_subjects known to the system. These are repeatedly identifiable groups or individuals';
COMMENT ON COLUMN known_subjects.parent_id IS 'In cases where known_subjects form a compositional hierarchy, this allows known_subjects to be linked to a parent known_subject.';
COMMENT ON COLUMN known_subjects.subject_type_id IS 'Identifies term describing the type of the subject. Foreign key to the termlists_terms table.';
COMMENT ON COLUMN known_subjects.description IS 'Description of the known subject.';
COMMENT ON COLUMN known_subjects.website_id IS 'Foreign key to the websites table. Identifies the website that this known_subjects is recorded on.';
COMMENT ON COLUMN known_subjects.created_on IS 'Date this record was created.';
COMMENT ON COLUMN known_subjects.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN known_subjects.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN known_subjects.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN known_subjects.deleted IS 'Has this record been deleted?';


-- Index: fki_known_subjects_known_subjects

-- DROP INDEX fki_known_subjects_known_subjects;

CREATE INDEX fki_known_subjects_known_subjects
  ON known_subjects
  USING btree
  (parent_id);

-- Index: fki_known_subjects_subject_types

-- DROP INDEX fki_known_subjects_subject_types;

CREATE INDEX fki_known_subjects_subject_types
  ON known_subjects
  USING btree
  (subject_type_id);

-- Index: fki_known_subjects_websites

-- DROP INDEX fki_known_subjects_websites;

CREATE INDEX fki_known_subjects_websites
  ON known_subjects
  USING btree
  (website_id);

-- Table: known_subject_comments

-- DROP TABLE known_subject_comments;

CREATE TABLE known_subject_comments
(
  id serial NOT NULL,
  known_subject_id integer NOT NULL, -- Foreign key to the known_subjects table. Identifies the commented known_subject.
  "comment" text NOT NULL,
  email_address character varying(50), -- Email of user who created the comment, if the user was not logged in but supplied an email address.
  person_name character varying, -- Identifier for anonymous commenter.
  created_by_id integer, -- Foreign key to the users table (creator), if user was logged in when comment created.
  created_on timestamp without time zone NOT NULL, -- Date and time this comment was created.
  updated_by_id integer, -- Foreign key to the users table (updater), if user was logged in when comment updated.
  updated_on timestamp without time zone NOT NULL, -- Date and time this comment was updated.
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_known_subject_comments PRIMARY KEY (id),
  CONSTRAINT fk_known_subject_comment_known_subject FOREIGN KEY (known_subject_id)
      REFERENCES known_subjects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_comment_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subject_comment_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE known_subject_comments OWNER TO indicia_user;
COMMENT ON TABLE known_subject_comments IS 'List of comments regarding the known_subject posted by users viewing the known_subject subsequent to initial data entry.';
COMMENT ON COLUMN known_subject_comments.known_subject_id IS 'Foreign key to the known_subjects table. Identifies the commented known_subject.';
COMMENT ON COLUMN known_subject_comments."comment" IS 'A user comment regarding the known_subject.';
COMMENT ON COLUMN known_subject_comments.email_address IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';
COMMENT ON COLUMN known_subject_comments.person_name IS 'Identifier for anonymous commenter.';
COMMENT ON COLUMN known_subject_comments.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';
COMMENT ON COLUMN known_subject_comments.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN known_subject_comments.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN known_subject_comments.updated_on IS 'Date and time this comment was updated.';
COMMENT ON COLUMN known_subject_comments.deleted IS 'Has this record been deleted?';

-- Index: fki_known_subject_comments_known_subject

-- DROP INDEX fki_known_subject_comments_known_subject;

CREATE INDEX fki_known_subject_comments_known_subject
  ON known_subject_comments
  USING btree
  (known_subject_id);

-- Link tables

-- Table: known_subjects_taxa_taxon_lists

-- DROP TABLE known_subjects_taxa_taxon_lists;

CREATE TABLE known_subjects_taxa_taxon_lists
(
  id serial NOT NULL,
  known_subject_id integer NOT NULL, -- Foreign key to the known_subjects table. Identifies the known subject that is identified by the taxa_taxon_list_id.
  taxa_taxon_list_id integer NOT NULL, -- Foreign key to the taxa_taxon_lists table. Identifies the taxon that the known subject belongs to.
  created_on timestamp without time zone NOT NULL, -- Date this record was created.
  created_by_id integer NOT NULL, -- Foreign key to the users table (creator).
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  CONSTRAINT pk_known_subjects_taxa_taxon_lists PRIMARY KEY (id),
  CONSTRAINT fk_known_subjects_taxa_taxon_lists_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subjects_taxa_taxon_lists_known_subjects FOREIGN KEY (known_subject_id)
      REFERENCES known_subjects (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_known_subjects_taxa_taxon_lists_taxa_taxon_lists FOREIGN KEY (taxa_taxon_list_id)
      REFERENCES taxa_taxon_lists (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);
-- ALTER TABLE known_subjects_taxa_taxon_lists OWNER TO indicia_user;
COMMENT ON TABLE known_subjects_taxa_taxon_lists IS 'Join table which identifies the taxa which the known subjects belong to.';
COMMENT ON COLUMN known_subjects_taxa_taxon_lists.known_subject_id IS 'Foreign key to the known_subjects table. Identifies the known subject that is identified by the taxa_taxon_list_id.';
COMMENT ON COLUMN known_subjects_taxa_taxon_lists.taxa_taxon_list_id IS 'Foreign key to the taxa_taxon_lists table. Identifies the taxon that the known subject belongs to.';
COMMENT ON COLUMN known_subjects_taxa_taxon_lists.created_on IS 'Date this record was created.';
COMMENT ON COLUMN known_subjects_taxa_taxon_lists.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN known_subjects_taxa_taxon_lists.deleted IS 'Has this record been deleted?';


-- Index: fki_known_subjects_taxa_taxon_lists_known_subject

-- DROP INDEX fki_known_subjects_taxa_taxon_lists_known_subject;

CREATE INDEX fki_known_subjects_taxa_taxon_lists_known_subject
  ON known_subjects_taxa_taxon_lists
  USING btree
  (known_subject_id);

-- Index: fki_known_subjects_taxa_taxon_lists_taxa_taxon_list

-- DROP INDEX fki_known_subjects_taxa_taxon_lists_taxa_taxon_list;

CREATE INDEX fki_known_subjects_taxa_taxon_lists_taxa_taxon_list
  ON known_subjects_taxa_taxon_lists
  USING btree
  (taxa_taxon_list_id);

-- Views

-- View: list_known_subjects

-- DROP VIEW list_known_subjects;

CREATE OR REPLACE VIEW list_known_subjects AS 
 SELECT ks.id, array_to_string(array_agg(t.taxon),', ') AS taxa, ks.subject_type_id, substring(ks.description from 1 for 30) as short_description, ks.website_id
   FROM known_subjects ks
   LEFT JOIN known_subjects_taxa_taxon_lists kst ON ks.id = kst.known_subject_id
   LEFT JOIN taxa_taxon_lists ttl ON kst.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
  WHERE ks.deleted = false
  GROUP BY ks.id, ks.subject_type_id, short_description, ks.website_id;

-- ALTER TABLE list_known_subjects OWNER TO indicia_user;

-- View: list_known_subject_comments

-- DROP VIEW list_known_subject_comments;

CREATE OR REPLACE VIEW list_known_subject_comments AS 
 SELECT ksc.id, ksc.comment, ksc.known_subject_id, ksc.email_address, ksc.updated_on, ksc.person_name, u.username, ks.website_id
   FROM known_subject_comments ksc
   JOIN known_subjects ks ON ks.id = ksc.known_subject_id
   LEFT JOIN users u ON ksc.created_by_id = u.id;

-- ALTER TABLE list_known_subject_comments OWNER TO indicia_user;

-- View: detail_known_subjects

-- DROP VIEW detail_known_subjects;

CREATE OR REPLACE VIEW detail_known_subjects AS 
 SELECT ks.id, array_to_string(array_agg(t.taxon),', ') AS taxa, ks.subject_type_id, ks.description, ks.website_id, kst.taxa_taxon_list_id, array_to_string(array_agg(ttl.taxon_meaning_id),', ') AS taxon_meaning_ids, ks.created_by_id, c.username AS created_by, ks.created_on, ks.updated_by_id, u.username AS updated_by, ks.updated_on, ks.deleted
   FROM known_subjects ks
   LEFT JOIN known_subjects_taxa_taxon_lists kst ON ks.id = kst.known_subject_id
   LEFT JOIN taxa_taxon_lists ttl ON kst.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   JOIN users c ON c.id = ks.created_by_id
   JOIN users u ON u.id = ks.updated_by_id
  WHERE ks.deleted = false
  GROUP BY ks.id, ks.subject_type_id, ks.description, ks.website_id, kst.taxa_taxon_list_id, ks.created_by_id, created_by, ks.created_on, ks.updated_by_id, updated_by, ks.updated_on, ks.deleted;

-- ALTER TABLE detail_known_subjects OWNER TO indicia_user;

-- View: gv_known_subjects

-- DROP VIEW gv_known_subjects;

CREATE OR REPLACE VIEW gv_known_subjects AS 
 SELECT ks.id, array_to_string(array_agg(t.taxon),', ') AS taxa, ks.subject_type_id, s_t.term AS subject_type, substring(ks.description from 1 for 30) as short_description, ks.website_id, ks.deleted
   FROM known_subjects ks
   LEFT JOIN known_subjects_taxa_taxon_lists kst ON ks.id = kst.known_subject_id
   LEFT JOIN taxa_taxon_lists ttl ON kst.taxa_taxon_list_id = ttl.id
   LEFT JOIN taxa t ON ttl.taxon_id = t.id
   LEFT JOIN termlists_terms s_tlt ON ks.subject_type_id = s_tlt.id
   LEFT JOIN terms s_t ON s_tlt.term_id = s_t.id
  WHERE ks.deleted = false
  GROUP BY ks.id, ks.subject_type_id, subject_type, short_description, ks.website_id, ks.deleted;

-- ALTER TABLE gv_known_subjects OWNER TO indicia_user;



