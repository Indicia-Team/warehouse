-- #slow script#

-- Re-insert incorrectly removed delete tasks
-- Also reprocess all training records.

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_summary_builder_sample', 'sample', s.id, 2, 50, now()
FROM samples s
LEFT JOIN work_queue wq ON wq.task='task_summary_builder_sample'
    AND wq.entity='sample' AND wq.record_id = s.id
WHERE s.parent_id IS NULL
AND ((s.deleted=false and s.training=true) OR (s.deleted=true and s.updated_on >= '2022-10-01')) 
AND survey_id IN (SELECT survey_id FROM summariser_definitions WHERE deleted = false)
AND wq.id IS NULL;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_summary_builder_occurrence_insert_delete', 'occurrence', o.id, 2, 40, now()
FROM occurrences o
LEFT JOIN work_queue wq ON wq.task='task_summary_builder_occurrence_insert_delete'
    AND wq.entity='occurrence' AND wq.record_id = o.id
WHERE o.deleted=true and o.updated_on >= '2022-10-01' 
AND wq.id IS NULL;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_summary_builder_location_delete', 'location', l.id, 3, 40, now()
FROM locations l
LEFT JOIN work_queue wq ON wq.task='task_summary_builder_location_delete'
    AND wq.entity='location' AND wq.record_id = l.id
WHERE l.deleted=true and l.updated_on >= '2022-10-01'
AND wq.id IS NULL;
