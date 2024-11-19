-- #slow script#

-- Re-insert incorrectly removed delete tasks

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_spatial_index_builder_location_delete', 'taxa_taxon_list_attribute_value', l.id, 3, 40, now()
FROM locations l
LEFT JOIN work_queue wq ON wq.task='task_spatial_index_builder_location_delete'
    AND wq.entity='location' AND wq.record_id = l.id
WHERE l.deleted=true and l.updated_on >= '2022-10-01'
AND wq.id IS NULL;

