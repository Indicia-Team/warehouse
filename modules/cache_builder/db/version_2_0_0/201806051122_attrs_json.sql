ALTER TABLE cache_occurrences_nonfunctional
  ADD COLUMN attrs_json json;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_occurrences', 'occurrences', id, 2, 60, now()
FROM occurrences
WHERE deleted=false;

ALTER TABLE cache_samples_nonfunctional
  ADD COLUMN attrs_json json;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_samples', 'samples', id, 2, 60, now()
FROM samples
WHERE deleted=false;