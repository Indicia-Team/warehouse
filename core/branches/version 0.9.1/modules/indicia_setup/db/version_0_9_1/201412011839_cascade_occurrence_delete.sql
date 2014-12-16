CREATE OR REPLACE FUNCTION cascade_occurrence_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrence_attribute_values SET deleted = true  WHERE occurrence_id  = OLD.id;
      UPDATE occurrence_comments SET deleted = true  WHERE occurrence_id  = OLD.id;
      UPDATE occurrence_media SET deleted = true  WHERE occurrence_id  = OLD.id;
      UPDATE determinations SET deleted = true WHERE occurrence_id = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';
