CREATE OR REPLACE FUNCTION cascade_location_delete()
  RETURNS trigger AS
$BODY$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE location_attribute_values SET deleted = true  WHERE location_id  = OLD.id;
      UPDATE location_media SET deleted = true  WHERE location_id  = OLD.id;
      UPDATE samples SET location_id=null, location_name=COALESCE(location_name, OLD.name) where location_id=OLD.id;
      UPDATE cache_occurrences SET location_id=null, location_name=COALESCE(location_name, OLD.name) where location_id=OLD.id;
    END IF;
  RETURN OLD;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;