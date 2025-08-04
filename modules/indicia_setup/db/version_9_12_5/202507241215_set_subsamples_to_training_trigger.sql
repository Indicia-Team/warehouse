DROP TRIGGER IF EXISTS set_sample_occurrences_to_training_trigger ON samples;

DROP FUNCTION IF EXISTS set_sample_occurrences_to_training();

DROP TRIGGER IF EXISTS set_sample_children_training_flag_trigger ON samples;

DROP FUNCTION IF EXISTS set_sample_children_training_flag();

CREATE OR REPLACE FUNCTION set_sample_children_training_flag()
  RETURNS trigger AS
  $BODY$
    BEGIN
      IF ((OLD.training = false AND NEW.training = true) OR
          (OLD.training = true AND NEW.training = false)) THEN
          
        -- Set any subsample training flags if parent training flag changes
        UPDATE samples
        SET training = NEW.training, updated_on=now()
        WHERE parent_id = NEW.id;
        -- Do same thing for the cache table, and also update the cache table for the sample being changed.
        UPDATE cache_samples_functional
        SET training = NEW.training, updated_on=now()
        WHERE 
        id = NEW.id OR parent_sample_id = NEW.id;
        -- Similarly update occurrences for the sample training flag change.
        -- Also update occurrences for any subsamples changed
        -- as a result of parent sample training flag change.
        UPDATE occurrences
        SET training = NEW.training, updated_on=now()
        FROM samples s
        WHERE sample_id = s.id AND
        (s.id = NEW.id OR
        s.parent_id = NEW.id);
        -- Exactly the same for cache table
        UPDATE cache_occurrences_functional
        SET training = NEW.training, updated_on=now()
        FROM samples s
        WHERE sample_id = s.id AND
        (s.id = NEW.id OR
        s.parent_id = NEW.id);

      END IF;
      RETURN OLD;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;


CREATE TRIGGER set_sample_children_training_flag_trigger
  AFTER UPDATE
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_sample_children_to_training();

DROP TRIGGER IF EXISTS set_new_subsample_to_training_from_parent_sample_trigger ON samples;

DROP FUNCTION IF EXISTS  set_new_subsample_to_training_from_parent_sample();

CREATE OR REPLACE FUNCTION set_new_subsample_to_training_from_parent_sample()
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

CREATE TRIGGER set_new_subsample_to_training_from_parent_sample_trigger
  AFTER INSERT
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_subsample_to_training_from_sample();

DROP TRIGGER IF EXISTS set_occurrence_to_training_from_sample_trigger ON occurrences;

DROP FUNCTION IF EXISTS set_occurrence_to_training_from_sample(); 

DROP TRIGGER IF EXISTS set_new_occurrence_to_training_from_its_sample_trigger ON occurrences;

DROP FUNCTION IF EXISTS set_new_occurrence_to_training_from_its_sample(); 

CREATE OR REPLACE FUNCTION set_new_occurrence_to_training_from_its_sample()
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


CREATE TRIGGER set_new_occurrence_to_training_from_its_sample_trigger
  AFTER INSERT
  ON occurrences
  FOR EACH ROW
  EXECUTE PROCEDURE set_occurrence_to_training_from_sample();