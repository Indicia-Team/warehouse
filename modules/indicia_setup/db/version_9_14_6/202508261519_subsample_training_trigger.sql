-- Switch to using BEFORE (not AFTER) INSERT.
CREATE OR REPLACE TRIGGER set_new_subsample_to_training_from_parent_sample_trigger
    BEFORE INSERT
    ON indicia.samples
    FOR EACH ROW
    EXECUTE FUNCTION indicia.set_new_subsample_to_training_from_parent_sample();

CREATE OR REPLACE FUNCTION set_new_subsample_to_training_from_parent_sample()
  RETURNS trigger AS
  $BODY$
    BEGIN

      IF NEW.training=false AND NEW.parent_id IS NOT NULL THEN
        IF EXISTS(SELECT training FROM samples WHERE id=NEW.parent_id AND training=true) THEN

          NEW.training := true;

        END IF;
      END IF;

      RETURN NEW;
    END;
    $BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;