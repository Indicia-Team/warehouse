-- #slow script#

-- Re-insert incorrectly removed delete tasks

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attr_value_occurrence', 'occurrence_attribute_value', oav.id, 2, 30, now()
FROM occurrence_attribute_values oav
LEFT JOIN work_queue wq ON wq.task='task_cache_builder_attr_value_occurrence'
    AND wq.entity='occurrence_attribute_value' AND wq.record_id = oav.id
WHERE oav.deleted=true and oav.updated_on >= '2022-10-01'
AND wq.id IS NULL;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attr_value_sample', 'sample_attribute_value', sav.id, 2, 30, now()
FROM sample_attribute_values sav
LEFT JOIN work_queue wq ON wq.task='task_cache_builder_attr_value_sample'
    AND wq.entity='sample_attribute_value' AND wq.record_id = sav.id
WHERE sav.deleted=true and sav.updated_on >= '2022-10-01'
AND wq.id IS NULL;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attr_value_taxa_taxon_list', 'taxa_taxon_list_attribute_value', ttlav.id, 2, 30, now()
FROM taxa_taxon_list_attribute_values ttlav
LEFT JOIN work_queue wq ON wq.task='task_cache_builder_attr_value_taxa_taxon_list'
    AND wq.entity='taxa_taxon_list_attribute_value' AND wq.record_id = ttlav.id
WHERE ttlav.deleted=true and ttlav.updated_on >= '2022-10-01'
AND wq.id IS NULL;
