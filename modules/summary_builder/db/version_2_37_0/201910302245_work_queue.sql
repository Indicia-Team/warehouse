-- #slow script#

-- trigger a rebuild of any modified samples. Only bother with 2019 and later modifications, as a full rebuild
-- was done during 2019 with the deployment of the new version of the summary builder.

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_summary_builder_sample', 'sample', id, 2, 50, now()
FROM samples
WHERE deleted=false
AND parent_id IS NULL
AND survey_id IN (SELECT survey_id FROM summariser_definitions WHERE deleted = false)
AND created_on <> updated_on
AND updated_on >= CAST('2019-01-01' as date)
;