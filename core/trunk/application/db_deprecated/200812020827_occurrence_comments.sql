-- Table: occurrence_comments

-- DROP TABLE occurrence_comments;

CREATE TABLE occurrence_comments
(
  id serial NOT NULL,
  "comment" text NOT NULL,
  created_by_id integer NOT NULL, -- User who entered the comment. Foreign key to the users table (creator).
  created_on timestamp without time zone NOT NULL, -- Date and time this comment was created.
  updated_by_id integer NOT NULL, -- Foreign key to the users table (updater).
  updated_on timestamp without time zone NOT NULL, -- Date and time this comment was updated.
  occurrence_id integer, -- Foreign key to the occurrences table. Identifies the commented occurrence.
  CONSTRAINT pk_occurrence_comments PRIMARY KEY (id),
  CONSTRAINT fk_occurrence_comment_creator FOREIGN KEY (created_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_comment_occurrence FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION,
  CONSTRAINT fk_occurrence_comment_updater FOREIGN KEY (updated_by_id)
      REFERENCES users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
)
WITH (OIDS=FALSE);
ALTER TABLE occurrence_comments OWNER TO postgres;
COMMENT ON COLUMN occurrence_comments.created_by_id IS 'User who entered the comment. Foreign key to the users table (creator).';
COMMENT ON COLUMN occurrence_comments.created_on IS 'Date and time this comment was created.';
COMMENT ON COLUMN occurrence_comments.updated_by_id IS 'Foreign key to the users table (updater).';
COMMENT ON COLUMN occurrence_comments.updated_on IS 'Date and time this comment was updated.';
COMMENT ON COLUMN occurrence_comments.occurrence_id IS 'Foreign key to the occurrences table. Identifies the commented occurrence.';