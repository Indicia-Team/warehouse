-- #slow script#

-- trigger a rebuild of the entire data table, using the the work queue.

DELETE FROM work_queue WHERE task='task_summary_builder_sample';

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_summary_builder_sample', 'sample', id, 2, 50, now()
FROM samples
WHERE deleted=false
AND parent_id IS NULL
AND survey_id IN (SELECT survey_id FROM summariser_definitions WHERE deleted = false);