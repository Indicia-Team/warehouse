-- #slow script#
-- Remove cache rows left behind for samples which are deleted, including
-- samples whose parent was deleted.
CREATE TEMPORARY TABLE tmp_deleted_sample_tree AS
WITH RECURSIVE deleted_sample_tree(id) AS (
  SELECT id
  FROM samples
  WHERE deleted=true

  UNION

  SELECT child.id
  FROM samples child
  JOIN deleted_sample_tree ancestor ON ancestor.id=child.parent_id
)
SELECT id
FROM deleted_sample_tree;

DELETE FROM cache_samples_functional
WHERE id IN (SELECT id FROM tmp_deleted_sample_tree);

DELETE FROM cache_samples_nonfunctional
WHERE id IN (SELECT id FROM tmp_deleted_sample_tree);

DELETE FROM cache_samples_sensitive
WHERE id IN (SELECT id FROM tmp_deleted_sample_tree);

CREATE TEMPORARY TABLE tmp_deleted_occurrence_ids AS
SELECT o.id
FROM occurrences o
JOIN tmp_deleted_sample_tree s ON s.id=o.sample_id;

DELETE FROM cache_occurrences_nonfunctional
WHERE id IN (SELECT id FROM tmp_deleted_occurrence_ids);

DELETE FROM cache_occurrences_functional
WHERE id IN (SELECT id FROM tmp_deleted_occurrence_ids);