DROP TRIGGER IF EXISTS set_sample_occurrences_to_training_trigger ON samples;

DROP FUNCTION IF EXISTS set_sample_occurrences_to_training();

DROP TRIGGER IF EXISTS set_sample_children_to_training_trigger ON samples;

DROP FUNCTION IF EXISTS set_sample_children_to_training();

CREATE OR REPLACE FUNCTION set_sample_children_to_training()
  RETURNS trigger AS
  $BODY$
    BEGIN
      IF (OLD.training = false AND NEW.training = true) THEN

        UPDATE cache_samples_functional
        SET training = true, updated_on=now()
        WHERE id = NEW.id;
      
        UPDATE samples
        SET training = true, updated_on=now()
        WHERE parent_id = NEW.id;

        UPDATE cache_samples_functional
        SET training = true, updated_on=now()
        WHERE parent_sample_id = NEW.id;

        UPDATE occurrences
        SET training = true, updated_on=now()
        WHERE sample_id = NEW.id;

        UPDATE cache_occurrences_functional
        SET training = true, updated_on=now()
        WHERE sample_id = NEW.id;

        UPDATE occurrences o
        SET training = true, updated_on=now()
        FROM samples s_sub
        WHERE s_sub.parent_id = NEW.id AND o.sample_id = s_sub.id;

        UPDATE cache_occurrences_functional o
        SET training = true, updated_on=now()
        FROM samples s_sub
        WHERE s_sub.parent_id = NEW.id AND o.sample_id = s_sub.id;

      END IF;
      RETURN OLD;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


CREATE TRIGGER set_sample_children_to_training_trigger
  AFTER UPDATE
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_sample_children_to_training();

DROP TRIGGER IF EXISTS set_subsample_to_training_from_sample_trigger ON samples;

DROP FUNCTION IF EXISTS set_subsample_to_training_from_sample();

CREATE OR REPLACE FUNCTION set_subsample_to_training_from_sample()
  RETURNS trigger AS
  $BODY$
    BEGIN

      UPDATE samples s_sub
      SET training = true, updated_on=now()
      FROM samples s_parent
      WHERE s_sub.parent_id = s_parent.id AND s_sub.id=NEW.id AND s_parent.training = true;
      RETURN NEW;

      UPDATE cache_samples_functional s_sub
      SET training = true, updated_on=now()
      FROM samples s_parent
      WHERE s_sub.parent_sample_id = s_parent.id AND s_sub.id=NEW.id AND s_parent.training = true;
      
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER set_subsample_to_training_from_sample_trigger
  AFTER INSERT
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_subsample_to_training_from_sample();

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

      UPDATE cache_occurrences_functional cof
      SET training = true, updated_on=now()
      FROM samples s
      WHERE cof.sample_id = s.id AND cof.id=NEW.id AND s.training = true;

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