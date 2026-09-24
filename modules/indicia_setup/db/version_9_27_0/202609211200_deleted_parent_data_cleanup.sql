-- #slow script#
-- Soft-delete source records which are still live beneath a deleted sample.
-- UNION (rather than UNION ALL) prevents cycles in the sample tree from
-- causing recursive evaluation to continue indefinitely.
WITH RECURSIVE deleted_sample_tree(id) AS (
  SELECT id
  FROM samples
  WHERE deleted=true

  UNION

  SELECT child.id
  FROM samples child
  JOIN deleted_sample_tree ancestor ON ancestor.id=child.parent_id
)
UPDATE samples s
SET deleted=true,
    updated_on=now(),
    updated_by_id=1
WHERE s.deleted=false
AND s.id IN (SELECT id FROM deleted_sample_tree);

-- Cover occurrences belonging to samples which were already deleted before
-- this migration, as well as occurrences exposed by the sample backfill above.
UPDATE occurrences o
SET deleted=true,
    updated_on=now(),
    updated_by_id=1
FROM samples s
WHERE s.deleted=true
AND o.sample_id=s.id
AND o.deleted=false;

-- Keep work queued for a sample subtree from processing records which have
-- been soft-deleted by a parent sample deletion.
CREATE OR REPLACE FUNCTION cascade_sample_delete() RETURNS TRIGGER AS $$
  BEGIN
    IF (OLD.deleted = false AND NEW.deleted = true) THEN
      UPDATE occurrences
      SET deleted = true, updated_on=now(), updated_by_id=new.updated_by_id
      WHERE sample_id = OLD.id;

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
      WHERE parent_id = OLD.id;

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
