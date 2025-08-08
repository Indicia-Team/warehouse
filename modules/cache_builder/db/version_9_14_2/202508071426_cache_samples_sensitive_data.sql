-- #slow script#

INSERT INTO cache_samples_sensitive (id)
SELECT s.id FROM cache_samples_functional s
WHERE sensitive = true OR private=true
ON CONFLICT (id) DO NOTHING;

INSERT INTO work_queue(task, entity, record_id, cost_estimate, priority, created_on)
SELECT 'task_spatial_index_builder_sample', 'sample', id, 100, 3, now()
FROM cache_samples_functional
WHERE private=true OR sensitive=true;