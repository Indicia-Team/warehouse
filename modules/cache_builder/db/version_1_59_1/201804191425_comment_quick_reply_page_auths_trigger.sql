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

DROP TRIGGER IF EXISTS delete_quick_reply_auth_trigger ON cache_occurrences_functional;

CREATE TRIGGER delete_quick_reply_auth_trigger
  AFTER UPDATE
  ON cache_occurrences_functional
  FOR EACH ROW
  EXECUTE PROCEDURE delete_quick_reply_auth();
