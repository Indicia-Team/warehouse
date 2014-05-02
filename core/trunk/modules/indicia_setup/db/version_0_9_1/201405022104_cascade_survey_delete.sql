CREATE OR REPLACE FUNCTION cascade_survey_delete()
   RETURNS trigger AS
$BODY$
   BEGIN
     IF (OLD.deleted = false AND NEW.deleted = true) THEN
       UPDATE samples SET deleted = true, updated_on=now() WHERE survey_id = OLD.id AND deleted=false;
     END IF;
   RETURN OLD;
END;
$BODY$
   LANGUAGE plpgsql;

CREATE TRIGGER survey_delete_trigger
   AFTER UPDATE
   ON surveys
   FOR EACH ROW
   EXECUTE PROCEDURE cascade_survey_delete();

CREATE OR REPLACE FUNCTION cascade_sample_delete()
  RETURNS trigger AS
$BODY$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrences SET deleted = true, updated_on=now() WHERE sample_id = OLD.id AND deleted=false;
      UPDATE sample_attribute_values SET deleted = true, updated_on=now() WHERE sample_id = OLD.id AND deleted=false;
      UPDATE sample_comments SET deleted = true, updated_on=now() WHERE sample_id = OLD.id AND deleted=false;
      UPDATE sample_media SET deleted = true, updated_on=now() WHERE sample_id = OLD.id AND deleted=false;
    END IF;
  RETURN OLD;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;

CREATE OR REPLACE FUNCTION cascade_occurrence_delete()
  RETURNS trigger AS
$BODY$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrence_attribute_values SET deleted = true, updated_on=now() WHERE occurrence_id = OLD.id AND deleted=false;
      UPDATE occurrence_comments SET deleted = true, updated_on=now() WHERE occurrence_id = OLD.id AND deleted=false;
      UPDATE occurrence_media SET deleted = true, updated_on=now() WHERE occurrence_id = OLD.id AND deleted=false;
    END IF;
  RETURN OLD;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;