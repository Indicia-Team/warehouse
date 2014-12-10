-- Table: survey_comments

-- DROP TABLE survey_comments;

CREATE TABLE survey_comments
(
  id serial NOT NULL,
  comment text NOT NULL,
  created_by_id integer, -- Foreign key to the users table (creator), if user was logged in when comment created.
  created_on timestamp without time zone NOT NULL, -- Date and time this comment was created.
  updated_by_id integer, -- Foreign key to the users table (updater), if user was logged in when comment updated.
  updated_on timestamp without time zone NOT NULL, -- Date and time this comment was updated.
  survey_id integer, -- Foreign key to the surveys table. Identifies the commented survey.
  email_address character varying(50), -- Email of user who created the comment, if the user was not logged in but supplied an email address.
  deleted boolean NOT NULL DEFAULT false, -- Has this record been deleted?
  person_name character varying, -- Identifier for anonymous commenter.
  CONSTRAINT pk_survey_comments PRIMARY KEY (id ),
  CONSTRAINT fk_survey_comment_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_comment_survey FOREIGN KEY (survey_id)
      REFERENCES surveys (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_survey_comment_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE survey_comments
  IS 'List of comments regarding the survey.';
COMMENT ON COLUMN survey_comments.created_by_id IS 'Foreign key to the users table (creator), if user was logged in when comment created.';
COMMENT ON COLUMN survey_comments.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN survey_comments.updated_by_id IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';
COMMENT ON COLUMN survey_comments.updated_on IS 'Date and time this comment was updated.';
COMMENT ON COLUMN survey_comments.survey_id IS 'Foreign key to the surveys table. Identifies the commented survey.';
COMMENT ON COLUMN survey_comments.email_address IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';
COMMENT ON COLUMN survey_comments.deleted IS 'Has this record been deleted?';
COMMENT ON COLUMN survey_comments.person_name IS 'Identifier for anonymous commenter.';