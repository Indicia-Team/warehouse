CREATE TABLE sample_comments
(
  id serial NOT NULL,
  "comment" text NOT NULL,
  created_by_id integer, -- Foreign key to the users table (creator), if user was logged in when comment created.
  created_on timestamp without time zone NOT NULL, -- Date and time this comment was created.
  updated_by_id integer, -- Foreign key to the users table (updater), if user was logged in when comment updated.
  updated_on timestamp without time zone NOT NULL, -- Date and time this comment was updated.
  sample_id integer, -- Foreign key to the samples table. Identifies the commented sample.
  email_address character varying(50), -- Email of user who created the comment, if the user was not logged in but supplied an email address.
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  person_name character varying, -- Identifier for anonymous commenter.
  CONSTRAINT pk_sample_comments PRIMARY KEY (id),
  CONSTRAINT fk_sample_comment_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_comment_sample FOREIGN KEY (sample_id)
      REFERENCES samples (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_sample_comment_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE sample_comments IS 'List of comments regarding the sample posted by users viewing the sample subsequent to initial data entry.';
COMMENT ON COLUMN sample_comments.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';
COMMENT ON COLUMN sample_comments.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN sample_comments.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN sample_comments.updated_on IS 'Date and time this comment was updated.';
COMMENT ON COLUMN sample_comments.sample_id IS 'Foreign key to the samples table. Identifies the commented sample.';
COMMENT ON COLUMN sample_comments.email_address IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';
COMMENT ON COLUMN sample_comments.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN sample_comments.person_name IS 'Identifier for anonymous commenter.';

DROP VIEW IF EXISTS list_sample_comments;

CREATE OR REPLACE VIEW list_sample_comments AS
 SELECT sc.id, sc.comment, sc.sample_id, sc.email_address, sc.updated_on, sc.person_name, u.username, su.website_id
   FROM sample_comments sc
		JOIN samples s on (s.id = sc.sample_id)
		JOIN surveys su on (su.id = s.survey_id)
   LEFT JOIN users u ON sc.created_by_id = u.id;


