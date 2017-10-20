
CREATE OR REPLACE FUNCTION cascade_group_release()
  RETURNS trigger AS
$BODY$
BEGIN
  IF COALESCE(OLD.private_records, false) <> COALESCE(NEW.private_records, false) THEN
    UPDATE occurrences o
    SET release_status=CASE COALESCE(NEW.private_records, false) WHEN true THEN 'U' ELSE 'R' END
    FROM samples s
    WHERE s.id=o.sample_id
    AND s.group_id=NEW.id
    AND o.release_status<>CASE COALESCE(NEW.private_records, false) WHEN true THEN 'U' ELSE 'R' END;

    UPDATE cache_occurrences_functional o
    SET release_status=CASE COALESCE(NEW.private_records, false) WHEN true THEN 'U' ELSE 'R' END
    FROM samples s
    WHERE s.id=o.sample_id
    AND s.group_id=NEW.id
    AND o.release_status<>CASE COALESCE(NEW.private_records, false) WHEN true THEN 'U' ELSE 'R' END;
  END IF;
  RETURN null;
END;
$BODY$
  LANGUAGE plpgsql VOLATILE
  COST 100;