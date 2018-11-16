-- In case there is unreliability in any of the v1 index_locations_samples data
-- prompt a rebuild using the work queue.
INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_spatial_index_builder_sample', 'sample', id, 2, 60, now()
FROM samples
WHERE deleted=false;