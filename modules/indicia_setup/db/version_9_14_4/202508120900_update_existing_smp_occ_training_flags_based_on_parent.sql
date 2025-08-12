-- #slow script#

-- Update any subsample training flags if the parent sample is training.
with updated as (
UPDATE samples s1
SET training = true, updated_on=now(), updated_by_id=1
FROM samples s2
WHERE s2.id = s1.parent_id AND s1.training = false AND s2.training = true
RETURNING s1.id)
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT 'task_cache_builder_update', 'sample', updated.id, 100, 2, now()
FROM updated
LEFT JOIN work_queue q ON q.task='task_cache_builder_update' AND q.entity='sample'
AND q.record_id = updated.id
WHERE q.id IS NULL;

-- Make sure all the occurrences are also training for any training samples.
with updated as (
UPDATE occurrences o
SET training = true, updated_on=now(), updated_by_id=1
FROM samples s
WHERE o.sample_id = s.id AND o.training = false AND s.training = true
RETURNING o.id)
INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT 'task_cache_builder_update', 'occurrence', updated.id, 100, 2, now()
FROM updated
LEFT JOIN work_queue q ON q.task='task_cache_builder_update' AND q.entity='occurrence'
AND q.record_id = updated.id
WHERE q.id IS NULL;