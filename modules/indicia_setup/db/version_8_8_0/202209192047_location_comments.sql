-- Table: location_comments

-- DROP TABLE location_comments;

CREATE TABLE IF NOT EXISTS location_comments
(
    id serial NOT NULL,
    comment text NOT NULL,
    created_by_id integer,
    created_on timestamp without time zone NOT NULL,
    updated_by_id integer,
    updated_on timestamp without time zone NOT NULL,
    location_id integer,
    email_address character varying(50),
    deleted boolean NOT NULL DEFAULT false,
    person_name character varying,
    external_key character varying(50),
    CONSTRAINT pk_location_comments PRIMARY KEY (id),
    CONSTRAINT fk_location_comment_creator FOREIGN KEY (created_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT fk_location_comment_location FOREIGN KEY (location_id)
        REFERENCES locations (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION,
    CONSTRAINT fk_location_comment_updater FOREIGN KEY (updated_by_id)
        REFERENCES users (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE NO ACTION
);

COMMENT ON TABLE location_comments
    IS 'List of comments regarding the location posted by users viewing the location subsequent to initial data entry.';

COMMENT ON COLUMN location_comments.comment
    IS 'Location comment text.';

COMMENT ON COLUMN location_comments.created_by_id
    IS 'Foreign key to the users table (creator), if user was logged in when comment created.';

COMMENT ON COLUMN location_comments.created_on
    IS 'Date and time this comment was created.';

COMMENT ON COLUMN location_comments.updated_by_id
    IS 'Foreign key to the users table (updater), if user was logged in when comment updated.';

COMMENT ON COLUMN location_comments.updated_on
    IS 'Date and time this comment was updated.';

COMMENT ON COLUMN location_comments.location_id
    IS 'Foreign key to the locations table. Identifies the commented location.';

COMMENT ON COLUMN location_comments.email_address
    IS 'Email of user who created the comment, if the user was not logged in but supplied an email address.';

COMMENT ON COLUMN location_comments.deleted
    IS 'Has this record been deleted?';

COMMENT ON COLUMN location_comments.person_name
    IS 'Identifier for anonymous commenter.';

COMMENT ON COLUMN location_comments.external_key
    IS 'For comments imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';