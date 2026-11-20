CREATE OR REPLACE FUNCTION cascade_sample_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      -- Keep the original deletion metadata for reversal reference downloads.
      UPDATE occurrences
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id = OLD.id AND NOT deleted;

      UPDATE sample_attribute_values
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id = OLD.id;

      UPDATE sample_comments
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id = OLD.id;

      UPDATE sample_media
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id = OLD.id;

      UPDATE samples
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE parent_id = OLD.id AND NOT deleted;

      WITH RECURSIVE sample_tree(id) AS (
        SELECT NEW.id

        UNION

        SELECT child.id
        FROM samples child
        JOIN sample_tree ancestor ON ancestor.id=child.parent_id
      )
      DELETE FROM work_queue
      WHERE (entity='sample' AND record_id IN (SELECT id FROM sample_tree))
      OR (entity='occurrence' AND record_id IN (
        SELECT o.id
        FROM occurrences o
        JOIN sample_tree st ON st.id=o.sample_id
      ));
    END IF;
  RETURN OLD;
END;
$$ LANGUAGE 'plpgsql';
