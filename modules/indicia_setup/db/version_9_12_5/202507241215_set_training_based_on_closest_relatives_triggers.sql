-- Drop existing trigger as covered by new trigger
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
        -- Also update the sample that has actually changed (NEW.id) so then the cache table
        -- is updated at the same time for all the changed records
        
        with updated as (
        UPDATE samples
        SET training = NEW.training, updated_on=now(), updated_by_id = NEW.updated_by_id
        WHERE id = NEW.id OR
        parent_id = NEW.id
        RETURNING id, training)
        UPDATE cache_samples_functional csf
        -- Cache table does not have updated_by_id
        SET training = updated.training, updated_on=now()
        FROM updated
        WHERE csf.id = updated.id;

        -- Similarly update occurrences for the sample training flag change.
        -- Also update occurrences for any subsamples changed
        -- as a result of parent sample training flag change.
        with updated as (
        UPDATE occurrences o
        SET training = NEW.training, updated_on=now(), updated_by_id = NEW.updated_by_id
        FROM samples s
        WHERE sample_id = s.id AND
        (s.id = NEW.id OR
        s.parent_id = NEW.id)
        RETURNING o.id, o.training)
        UPDATE cache_occurrences_functional cof
        -- Cache table does not have updated_by_id
        SET training = updated.training, updated_on=now()
        FROM updated
        WHERE cof.id = updated.id;

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
  EXECUTE PROCEDURE set_sample_children_training_flag();

DROP TRIGGER IF EXISTS set_new_subsample_to_training_from_parent_sample_trigger ON samples;

DROP FUNCTION IF EXISTS  set_new_subsample_to_training_from_parent_sample();

CREATE OR REPLACE FUNCTION set_new_subsample_to_training_from_parent_sample()
  RETURNS trigger AS
  $BODY$
    BEGIN
    
      with updated as (
      UPDATE samples s_sub
      SET training = true, updated_on=now()
      FROM samples s_parent
      WHERE s_sub.parent_id = s_parent.id AND s_sub.id=NEW.id AND s_parent.training = true
      RETURNING s_sub.id, s_sub.training)
      UPDATE cache_samples_functional csf
      SET training = updated.training, updated_on=now()
      FROM updated
      WHERE csf.id = updated.id;
      
      RETURN NEW;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER set_new_subsample_to_training_from_parent_sample_trigger
  AFTER INSERT
  ON samples
  FOR EACH ROW
  EXECUTE PROCEDURE set_new_subsample_to_training_from_parent_sample();
-- Drop existing trigger as covered by new trigger
DROP TRIGGER IF EXISTS set_occurrence_to_training_from_sample_trigger ON occurrences;

DROP FUNCTION IF EXISTS set_occurrence_to_training_from_sample(); 

DROP TRIGGER IF EXISTS set_new_occurrence_to_training_from_its_sample_trigger ON occurrences;

DROP FUNCTION IF EXISTS set_new_occurrence_to_training_from_its_sample(); 

CREATE OR REPLACE FUNCTION set_new_occurrence_to_training_from_its_sample()
  RETURNS trigger AS
  $BODY$
    BEGIN

      with updated as (
      UPDATE occurrences o
      SET training = true, updated_on=now()
      FROM samples s
      WHERE o.sample_id = s.id AND o.id=NEW.id AND s.training = true
      RETURNING o.id, o.training)
      UPDATE cache_occurrences_functional cof
      SET training = updated.training, updated_on=now()
      FROM updated
      WHERE cof.id = updated.id;

      RETURN NEW;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE TRIGGER set_new_occurrence_to_training_from_its_sample_trigger
  AFTER INSERT
  ON occurrences
  FOR EACH ROW
  EXECUTE PROCEDURE set_new_occurrence_to_training_from_its_sample();