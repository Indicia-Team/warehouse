DROP TRIGGER IF EXISTS set_occurrences_to_training_trigger ON samples;

DROP FUNCTION IF EXISTS set_occurrences_to_training();

CREATE OR REPLACE FUNCTION set_occurrences_to_training()
  RETURNS trigger AS
  $BODY$
    BEGIN
	IF (OLD.training = false AND NEW.training = true) THEN
		UPDATE occurrences
		SET training = true, updated_on=now() 
		FROM samples s
		WHERE sample_id = s.id and s.id=OLD.id;
	END IF;
	RETURN OLD;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER set_occurrences_to_training_trigger
  AFTER UPDATE
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_occurrences_to_training();