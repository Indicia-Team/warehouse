CREATE OR REPLACE FUNCTION cascade_group_release() RETURNS TRIGGER AS $$
DECLARE
  changeTo character(1) default '';
  changeFrom character(1) default '';
BEGIN
  IF (OLD.private_records = false AND NEW.private_records = true) THEN
    changeTo = 'U';
    changeFrom = 'R';
  ELSEIF (OLD.private_records = true AND NEW.private_records = false) THEN
    changeTo = 'R';
    changeFrom = 'U';
  END IF;
  IF changeTo<>'' THEN
    UPDATE occurrences o SET release_status=changeTo
    FROM samples s
    WHERE s.id=o.sample_id 
    AND s.group_id=NEW.id
    AND o.release_status=changeFrom;

    UPDATE cache_occurrences o SET release_status=changeTo
    FROM samples s
    WHERE s.id=o.sample_id 
    AND s.group_id=NEW.id
    AND o.release_status=changeFrom;
  END IF;
  RETURN null;
END;
$$ LANGUAGE 'plpgsql';

DROP TRIGGER IF EXISTS group_release_trigger ON groups;
CREATE TRIGGER group_release_trigger AFTER UPDATE ON groups FOR EACH ROW EXECUTE PROCEDURE cascade_group_release();
