-- #slow script#

-- Clear out, just in case the warehouse has been left live during the update.
DELETE FROM work_queue WHERE task IN (
  'task_cache_builder_attrs_occurrence',
  'task_cache_builder_attrs_sample',
  'task_cache_builder_attrs_taxa_taxon_list'
);

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_occurrence', 'occurrence', id, 3, 60, now()
FROM occurrences
WHERE deleted=false;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_sample', 'sample', id, 3, 60, now()
FROM samples
WHERE deleted=false;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_taxa_taxon_list', 'taxa_taxon_list', ttl.id, 3, 60, now()
FROM taxa_taxon_lists ttl
--May as well skip any without an attribute.
JOIN taxa_taxon_list_attribute_values av on av.taxa_taxon_list_id=ttl.id and av.deleted=false
WHERE ttl.deleted=false;