CREATE OR REPLACE FUNCTION cascade_sample_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrences
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id  = OLD.id;

      UPDATE sample_attribute_values
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id  = OLD.id;

      UPDATE sample_comments
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id  = OLD.id;

      UPDATE sample_media
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id  = OLD.id;

      UPDATE samples
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE parent_id = OLD.id;

    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';