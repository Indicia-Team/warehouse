CREATE OR REPLACE FUNCTION cascade_sample_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrences SET deleted = true  WHERE sample_id  = OLD.id;
      UPDATE sample_attribute_values SET deleted = true  WHERE sample_id  = OLD.id;
      UPDATE sample_comments SET deleted = true  WHERE sample_id  = OLD.id;
      UPDATE sample_media SET deleted = true  WHERE sample_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION cascade_occurrence_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrence_attribute_values SET deleted = true  WHERE occurrence_id  = OLD.id;
      UPDATE occurrence_comments SET deleted = true  WHERE occurrence_id  = OLD.id;
      UPDATE occurrence_media SET deleted = true  WHERE occurrence_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE OR REPLACE FUNCTION cascade_location_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE location_attribute_values SET deleted = true  WHERE location_id  = OLD.id;
      UPDATE location_media SET deleted = true  WHERE location_id  = OLD.id;
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';

CREATE TRIGGER sample_delete_trigger AFTER UPDATE ON samples FOR EACH ROW EXECUTE PROCEDURE cascade_sample_delete();

CREATE TRIGGER occurrence_delete_trigger AFTER UPDATE ON occurrences FOR EACH ROW EXECUTE PROCEDURE cascade_occurrence_delete();

CREATE TRIGGER location_delete_trigger AFTER UPDATE ON locations FOR EACH ROW EXECUTE PROCEDURE cascade_location_delete();

-- Clean up existing data
UPDATE occurrences SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
DELETE FROM cache_occurrences WHERE sample_id in (select id from samples where deleted=true);
UPDATE sample_attribute_values SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
UPDATE sample_comments SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;
UPDATE sample_media SET deleted = true WHERE sample_id in (select id from samples where deleted=true) AND deleted=false;

UPDATE occurrence_attribute_values SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;
UPDATE occurrence_comments SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;
UPDATE occurrence_media SET deleted = true WHERE occurrence_id in (select id from occurrences where deleted=true) and deleted=false;

UPDATE location_attribute_values SET deleted = true WHERE location_id in (select id from locations where deleted=true) and deleted=false;
UPDATE location_media SET deleted = true WHERE location_id in (select id from locations where deleted=true) and deleted=false;