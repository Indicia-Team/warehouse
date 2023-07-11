DROP TRIGGER IF EXISTS set_sample_occurrences_to_training_trigger ON samples;

DROP FUNCTION IF EXISTS set_sample_occurrences_to_training();

CREATE OR REPLACE FUNCTION set_sample_occurrences_to_training()
  RETURNS trigger AS
  $BODY$
    BEGIN
      IF (OLD.training = false AND NEW.training = true) THEN
        UPDATE occurrences
        SET training = true, updated_on=now()
        WHERE sample_id = NEW.id;
      END IF;
      RETURN OLD;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


CREATE TRIGGER set_sample_occurrences_to_training_trigger
  AFTER UPDATE
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_sample_occurrences_to_training();

DROP TRIGGER IF EXISTS set_occurrence_to_training_from_sample_trigger ON occurrences;

DROP FUNCTION IF EXISTS set_occurrence_to_training_from_sample();

CREATE OR REPLACE FUNCTION set_occurrence_to_training_from_sample()
  RETURNS trigger AS
  $BODY$
    BEGIN
      UPDATE occurrences o
      SET training = true, updated_on=now()
      FROM samples s
      WHERE o.sample_id = s.id AND o.id=NEW.id AND s.training = true;
      RETURN NEW;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


CREATE TRIGGER set_occurrence_to_training_from_sample_trigger
  AFTER INSERT
  ON occurrences
  FOR EACH ROW
  EXECUTE PROCEDURE set_occurrence_to_training_from_sample();