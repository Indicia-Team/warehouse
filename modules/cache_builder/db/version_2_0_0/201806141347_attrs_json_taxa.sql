ALTER TABLE cache_taxa_taxon_lists
  ADD COLUMN attrs_json json;

INSERT INTO work_queue (task, entity, record_id, priority, cost_estimate, created_on)
SELECT DISTINCT 'task_cache_builder_attrs_taxa_taxon_list', 'taxa_taxon_list', ttl.id, 2, 60, now()
FROM taxa_taxon_lists ttl
--May as well skip any without an attribute.
JOIN taxa_taxon_list_attribute_values av on av.taxa_taxon_list_id=ttl.id and av.deleted=false
WHERE ttl.deleted=false;