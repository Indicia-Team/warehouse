-- #slow script#

-- In case there is unreliability in any of the v1 index_locations_samples data
-- prompt a rebuild using the work queue.
DELETE FROM work_queue WHERE task='task_spatial_index_builder_sample';

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_spatial_index_builder_sample', 'sample', id, 3, 60, now()
FROM samples
WHERE deleted=false;