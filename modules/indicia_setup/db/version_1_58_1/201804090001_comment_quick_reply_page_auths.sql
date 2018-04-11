-- Table: comment_quick_reply_page_auths

-- DROP TABLE comment_quick_reply_page_auths;

CREATE TABLE comment_quick_reply_page_auths
(
  id serial NOT NULL,
  occurrence_id int not null,
  token varchar not null,
  created_on timestamp without time zone NOT NULL,
  created_by_id integer NOT NULL,
  updated_on timestamp without time zone NOT NULL,
  updated_by_id integer NOT NULL,
  deleted boolean NOT NULL DEFAULT false,
  CONSTRAINT pk_comment_quick_reply_page_auth PRIMARY KEY (id),
  CONSTRAINT fk_comment_quick_reply_page_auth_occurrence_id FOREIGN KEY (occurrence_id)
      REFERENCES occurrences (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE NO ACTION
);

COMMENT ON TABLE comment_quick_reply_page_auths
  IS 'List of valid authorisation tokens for use on the record comments page on the warehouse.';

COMMENT ON COLUMN comment_quick_reply_page_auths.occurrence_id IS 'Occurrence ID token is linked to.';
COMMENT ON COLUMN comment_quick_reply_page_auths.token IS 'Token to indicate that the record comments page is valid for use.';
COMMENT ON COLUMN comment_quick_reply_page_auths.created_on IS 'Date this record was created.';
COMMENT ON COLUMN comment_quick_reply_page_auths.created_by_id IS 'Foreign key to the users table (creator).';
COMMENT ON COLUMN comment_quick_reply_page_auths.updated_on IS 'Date this record was last updated.';
COMMENT ON COLUMN comment_quick_reply_page_auths.updated_by_id IS 'Foreign key to the users table (last updater).';
COMMENT ON COLUMN comment_quick_reply_page_auths.deleted IS 'Has this record been deleted?';

DROP VIEW IF EXISTS list_occurrences;

CREATE OR REPLACE VIEW list_comment_quick_reply_page_auths AS 
SELECT rcpat.id, rcpat.occurrence_id, rcpat.token
FROM comment_quick_reply_page_auths rcpat
WHERE rcpat.deleted = false;

CREATE OR REPLACE FUNCTION delete_quick_reply_auth()
  RETURNS trigger AS
  $BODY$
    BEGIN
      IF (OLD.query = 'Q' AND NEW.query != 'Q') THEN
        UPDATE comment_quick_reply_page_auths SET deleted = true, updated_on=now() WHERE occurrence_id = OLD.id AND deleted=false;
      END IF;
     RETURN OLD;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER delete_quick_reply_auth_trigger
  AFTER UPDATE
  ON cache_occurrences_functional
  FOR EACH ROW
  EXECUTE PROCEDURE delete_quick_reply_auth();
